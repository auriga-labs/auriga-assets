<?php
/* Auriga Assets — Subsonic 互換 API
   ローカル素材 DB (kind = bgm | se) を Subsonic クライアント
   (DSub / Symfonium / Amperfy / play:Sub など) から再生できるようにする。

   エンドポイント: <このアプリの URL>/rest/<メソッド>.view
   (.view なしでも可。rest/.htaccess が全リクエストをこのファイルに集約する)

   認証: config.php の subsonic_user / subsonic_pass。
   パスワード (p=平文 or enc:HEX) とトークン (t=md5(pass+salt) + s) の両方式に対応。
   subsonic_pass が空のままだと API は無効 (error 50) になる。

   マッピング:
     アーティスト = author (空なら provider)  … id "ar-<base64>"
     アルバム     = アーティスト × provider   … id "al-<base64>"
     曲           = assets の 1 行            … id = DB の整数 id
     ジャンル     = kind の大文字 (BGM / SE)
   ストリーミング: uploads/ は Range 対応で直接配信、外部 URL や YouTube は
   proxy.php を内部 include して中継する。 */

require_once __DIR__ . '/../db.php';
$KEYS      = require __DIR__ . '/../config.php';
$PROVIDERS = require __DIR__ . '/../providers.php';

const SUBSONIC_VERSION = '1.16.1';
const AUDIO_WHERE      = "kind IN ('bgm', 'se')";
const ARTIST_EXPR      = "CASE WHEN author = '' THEN provider ELSE author END";
const MIME_TYPES = [
    'mp3'  => 'audio/mpeg',  'm4a' => 'audio/mp4',   'ogg'  => 'audio/ogg',
    'wav'  => 'audio/wav',   'flac' => 'audio/flac', 'webm' => 'audio/webm',
    'jpg'  => 'image/jpeg',  'jpeg' => 'image/jpeg', 'png'  => 'image/png',
    'gif'  => 'image/gif',   'webp' => 'image/webp',
];

$P = array_merge($_GET, $_POST);

/* ---- レスポンス出力 (XML / JSON / JSONP) -------------------------------- */

/* 内部表現: 連想配列。スカラー値 → XML 属性 / JSON プロパティ、
   配列値 → 子要素 (リストなら同名要素の繰り返し)、'#text' → 要素テキスト */

function xml_node(string $name, array $data): string
{
    $attrs = '';
    $children = '';
    $text = null;
    foreach ($data as $k => $v) {
        if ($k === '#text') { $text = (string)$v; continue; }
        if (is_array($v)) {
            foreach (array_is_list($v) ? $v : [$v] as $child) $children .= xml_node($k, $child);
        } else {
            if (is_bool($v)) $v = $v ? 'true' : 'false';
            $attrs .= ' ' . $k . '="' . htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
        }
    }
    if ($text !== null) $children .= htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    return $children === '' ? "<{$name}{$attrs}/>" : "<{$name}{$attrs}>{$children}</{$name}>";
}

/* JSON 用に変換: '#text' → value、空配列 → 空オブジェクト */
function json_convert(array $data): object|array
{
    if (array_is_list($data) && $data !== []) {
        return array_map(fn($v) => is_array($v) ? json_convert($v) : $v, $data);
    }
    $out = new stdClass();
    foreach ($data as $k => $v) {
        if ($k === '#text') $k = 'value';
        $out->$k = is_array($v) ? json_convert($v) : $v;
    }
    return $out;
}

/* 空のリスト (子要素なし) は出力から省く。JSON で [] が {} に化けて
   クライアントを混乱させないため。コンテナ要素自体 (body 直下) は残す */
function strip_empty(array $a): array
{
    foreach ($a as $k => $v) {
        if (!is_array($v)) continue;
        $v = strip_empty($v);
        if ($v === []) unset($a[$k]);
        else $a[$k] = $v;
    }
    return $a;
}

function respond(array $body, string $status = 'ok'): never
{
    global $P;
    foreach ($body as $k => $v) {
        if (is_array($v)) $body[$k] = strip_empty($v);
    }
    $envelope = array_merge([
        'status'        => $status,
        'version'       => SUBSONIC_VERSION,
        'type'          => 'AurigaAssets',
        'serverVersion' => '1.0',
        'openSubsonic'  => true,
    ], $body);

    $format = $P['f'] ?? 'xml';
    if ($format === 'json' || $format === 'jsonp') {
        $json = json_encode(['subsonic-response' => json_convert($envelope)],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($format === 'jsonp') {
            $cb = preg_replace('/[^A-Za-z0-9_.$]/', '', $P['callback'] ?? 'callback');
            header('Content-Type: application/javascript; charset=utf-8');
            echo "{$cb}({$json});";
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo $json;
        }
    } else {
        header('Content-Type: text/xml; charset=utf-8');
        $envelope['xmlns'] = 'http://subsonic.org/restapi';
        echo '<?xml version="1.0" encoding="UTF-8"?>' . xml_node('subsonic-response', $envelope);
    }
    exit;
}

function fail_sub(int $code, string $message): never
{
    respond(['error' => ['code' => $code, 'message' => $message]], 'failed');
}

/* ---- ID エンコード (アーティスト名やプロバイダー id を URL 安全に埋め込む) - */

function b64e(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64d(string $s): string { return (string)base64_decode(strtr($s, '-_', '+/')); }

function artist_id(string $name): string { return 'ar-' . b64e($name); }
function album_id(string $artist, string $provider): string { return 'al-' . b64e($artist . "\x1f" . $provider); }

function decode_album_id(string $id): ?array
{
    if (!str_starts_with($id, 'al-')) return null;
    $parts = explode("\x1f", b64d(substr($id, 3)), 2);
    return count($parts) === 2 ? $parts : null;
}

/* ---- 認証 --------------------------------------------------------------- */

function check_auth(array $P, array $KEYS): void
{
    $user = (string)($KEYS['subsonic_user'] ?? '');
    $pass = (string)($KEYS['subsonic_pass'] ?? '');
    if ($user === '' || $pass === '') {
        fail_sub(50, 'Subsonic API is disabled. Set subsonic_user / subsonic_pass in config.php.');
    }
    $u = (string)($P['u'] ?? '');
    if ($u === '') fail_sub(10, 'Required parameter "u" is missing.');

    if (isset($P['t'], $P['s'])) {   /* トークン認証: t = md5(pass + salt) */
        if (hash_equals($u, $user) && hash_equals(md5($pass . $P['s']), strtolower((string)$P['t']))) return;
        fail_sub(40, 'Wrong username or password.');
    }
    $p = (string)($P['p'] ?? '');
    if ($p === '') fail_sub(10, 'Required parameter "p" (or "t" and "s") is missing.');
    if (str_starts_with($p, 'enc:')) $p = (string)hex2bin(substr($p, 4));
    if (hash_equals($u, $user) && hash_equals($pass, $p)) return;
    fail_sub(40, 'Wrong username or password.');
}

/* ---- データ取得 ---------------------------------------------------------- */

/* provider id → 表示ラベル (providers.php を平坦化) */
function provider_labels(array $PROVIDERS): array
{
    $labels = [];
    foreach ($PROVIDERS as $list) {
        foreach ($list as $p) $labels[$p['id']] = $p['label'];
    }
    return $labels;
}

function album_name(string $provider, string $kind): string
{
    global $LABELS;
    $label = $LABELS[$provider] ?? ($provider !== '' ? $provider : 'Unknown');
    return $kind === 'se' ? $label . ' (SE)' : $label;
}

/* 全アルバム = (アーティスト × provider) のグループ。件数は高々数百なので
   一括取得して PHP 側でソート・ページングする */
function all_albums(PDO $db, ?string $artist = null): array
{
    $sql = 'SELECT ' . ARTIST_EXPR . ' AS artist, provider, MIN(kind) AS kind,
                   COUNT(*) AS songCount, COALESCE(SUM(duration), 0) AS dur,
                   MAX(created_at) AS created
            FROM assets WHERE ' . AUDIO_WHERE;
    $params = [];
    if ($artist !== null) {
        $sql .= ' AND ' . ARTIST_EXPR . ' = :a';
        $params[':a'] = $artist;
    }
    $sql .= ' GROUP BY artist, provider';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function all_artists(PDO $db): array
{
    return $db->query(
        'SELECT ' . ARTIST_EXPR . ' AS name, COUNT(*) AS songCount,
                COUNT(DISTINCT provider) AS albumCount
         FROM assets WHERE ' . AUDIO_WHERE . '
         GROUP BY name ORDER BY name COLLATE NOCASE'
    )->fetchAll();
}

function album_entry(array $a): array
{
    $name = album_name($a['provider'], $a['kind']);
    $id   = album_id($a['artist'], $a['provider']);
    return [
        'id'        => $id,
        'name'      => $name,
        'title'     => $name,          /* 非 ID3 系クライアント向け */
        'album'     => $name,
        'artist'    => $a['artist'],
        'artistId'  => artist_id($a['artist']),
        'parent'    => artist_id($a['artist']),
        'isDir'     => true,
        'coverArt'  => $id,
        'songCount' => (int)$a['songCount'],
        'duration'  => (int)$a['dur'],
        'genre'     => strtoupper($a['kind']),
        'created'   => date('c', (int)($a['created'] ?: time())),
    ];
}

function artist_entry(array $a): array
{
    return [
        'id'         => artist_id($a['name']),
        'name'       => $a['name'],
        'albumCount' => (int)$a['albumCount'],
        'coverArt'   => artist_id($a['name']),
    ];
}

function song_suffix(string $preview): string
{
    $path = (string)parse_url($preview, PHP_URL_PATH);
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return isset(MIME_TYPES[$ext]) ? $ext : 'mp3';
}

function song_entry(array $row): array
{
    $artist  = $row['author'] !== '' ? $row['author'] : $row['provider'];
    $albumId = album_id($artist, $row['provider']);
    $suffix  = song_suffix($row['preview']);
    $entry = [
        'id'          => (string)$row['id'],
        'parent'      => $albumId,
        'isDir'       => false,
        'isVideo'     => false,
        'type'        => 'music',
        'title'       => $row['title'],
        'album'       => album_name($row['provider'], $row['kind']),
        'albumId'     => $albumId,
        'artist'      => $artist,
        'artistId'    => artist_id($artist),
        'genre'       => strtoupper($row['kind']),
        'coverArt'    => $row['thumb'] !== '' ? (string)$row['id'] : $albumId,
        'duration'    => (int)$row['duration'],
        'suffix'      => $suffix,
        'contentType' => MIME_TYPES[$suffix] ?? 'audio/mpeg',
        'path'        => $artist . '/' . $row['provider'] . '/' . $row['title'] . '.' . $suffix,
        'created'     => date('c', (int)($row['created_at'] ?: time())),
    ];
    if (str_starts_with($row['preview'], 'uploads/')
        && is_file($f = dirname(__DIR__) . '/' . $row['preview'])) {
        $entry['size'] = filesize($f);
    }
    return $entry;
}

function song_by_id(PDO $db, string $id): ?array
{
    if (!ctype_digit($id)) return null;
    $stmt = $db->prepare('SELECT * FROM assets WHERE id = :id AND ' . AUDIO_WHERE);
    $stmt->execute([':id' => (int)$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function album_songs(PDO $db, string $artist, string $provider): array
{
    $stmt = $db->prepare(
        'SELECT * FROM assets WHERE ' . AUDIO_WHERE . '
         AND ' . ARTIST_EXPR . ' = :a AND provider = :p
         ORDER BY title COLLATE NOCASE'
    );
    $stmt->execute([':a' => $artist, ':p' => $provider]);
    return $stmt->fetchAll();
}

/* インデックス用の先頭文字 (ASCII 英字は大文字、数字・記号は #、他はそのまま) */
function index_char(string $name): string
{
    $c = mb_substr($name, 0, 1, 'UTF-8');
    if ($c === '') return '#';
    if (preg_match('/^[A-Za-z]$/', $c)) return strtoupper($c);
    if (preg_match('/^[0-9!-\/:-@\[-`{-~ ]$/u', $c)) return '#';
    return $c;
}

function build_indexes(array $artists, callable $entryFn): array
{
    $groups = [];
    foreach ($artists as $a) {
        $groups[index_char($a['name'])][] = $entryFn($a);
    }
    uksort($groups, fn($x, $y) => strcmp($x === '#' ? "\x00" : $x, $y === '#' ? "\x00" : $y));
    $index = [];
    foreach ($groups as $char => $entries) {
        $index[] = ['name' => $char, 'artist' => $entries];
    }
    return $index;
}

/* ---- 配信 (stream / download / getCoverArt) ------------------------------ */

/* uploads/ 配下のローカルファイルを Range 対応で配信する */
function serve_local(string $rel): never
{
    $root = dirname(__DIR__);
    $abs  = realpath($root . '/' . $rel);
    if ($abs === false || !str_starts_with($abs, realpath($root . '/uploads') . DIRECTORY_SEPARATOR)) {
        fail_sub(70, 'File not found.');
    }
    $size = filesize($abs);
    $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: ' . (MIME_TYPES[$ext] ?? 'application/octet-stream'));
    header('Accept-Ranges: bytes');
    $start = 0;
    $end   = $size - 1;
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        if ($m[1] !== '') {
            $start = (int)$m[1];
            if ($m[2] !== '') $end = min((int)$m[2], $size - 1);
        } elseif ($m[2] !== '') {
            $start = max(0, $size - (int)$m[2]);
        }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header("Content-Range: bytes */{$size}");
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$size}");
    }
    header('Content-Length: ' . ($end - $start + 1));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') exit;
    $fp = fopen($abs, 'rb');
    fseek($fp, $start);
    $left = $end - $start + 1;
    while ($left > 0 && !feof($fp)) {
        $chunk = fread($fp, min(65536, $left));
        if ($chunk === false || $chunk === '') break;
        echo $chunk;
        flush();
        $left -= strlen($chunk);
    }
    fclose($fp);
    exit;
}

/* 外部 URL (ホットリンク保護・YouTube 含む) は proxy.php に委譲する */
function serve_media(string $preview): never
{
    if ($preview === '') fail_sub(70, 'Media not found.');
    if (str_starts_with($preview, 'uploads/')) serve_local($preview);
    $_GET['url'] = $preview;
    require dirname(__DIR__) . '/proxy.php';
    exit;
}

/* ---- ルーティング -------------------------------------------------------- */

$path   = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$method = basename($path);
if (str_ends_with($method, '.view')) $method = substr($method, 0, -5);
if ($method === '' || $method === 'index.php' || $method === 'rest') $method = (string)($P['m'] ?? 'ping');

$LABELS = provider_labels($PROVIDERS);

check_auth($P, $KEYS);

$db = assets_db();

switch ($method) {

case 'ping':
    respond([]);

case 'getLicense':
    respond(['license' => ['valid' => true]]);

case 'getOpenSubsonicExtensions':
    respond(['openSubsonicExtensions' => []]);

case 'getMusicFolders':
    respond(['musicFolders' => ['musicFolder' => [['id' => 1, 'name' => 'Auriga Assets']]]]);

case 'getIndexes':
    respond(['indexes' => [
        'lastModified'    => time() * 1000,
        'ignoredArticles' => '',
        'index'           => build_indexes(all_artists($db), fn($a) => [
            'id'   => artist_id($a['name']),
            'name' => $a['name'],
        ]),
    ]]);

case 'getArtists':
    respond(['artists' => [
        'ignoredArticles' => '',
        'index'           => build_indexes(all_artists($db), 'artist_entry'),
    ]]);

case 'getMusicDirectory': {
    $id = (string)($P['id'] ?? '');
    if (str_starts_with($id, 'ar-')) {
        $name   = b64d(substr($id, 3));
        $albums = all_albums($db, $name);
        if ($albums === []) fail_sub(70, 'Directory not found.');
        respond(['directory' => [
            'id'    => $id,
            'name'  => $name,
            'child' => array_map('album_entry', $albums),
        ]]);
    }
    if (($album = decode_album_id($id)) !== null) {
        [$artist, $provider] = $album;
        $songs = album_songs($db, $artist, $provider);
        if ($songs === []) fail_sub(70, 'Directory not found.');
        respond(['directory' => [
            'id'     => $id,
            'parent' => artist_id($artist),
            'name'   => album_name($provider, $songs[0]['kind']),
            'child'  => array_map('song_entry', $songs),
        ]]);
    }
    fail_sub(70, 'Directory not found.');
}

case 'getArtist': {
    $id = (string)($P['id'] ?? '');
    if (!str_starts_with($id, 'ar-')) fail_sub(70, 'Artist not found.');
    $name   = b64d(substr($id, 3));
    $albums = all_albums($db, $name);
    if ($albums === []) fail_sub(70, 'Artist not found.');
    respond(['artist' => [
        'id'         => $id,
        'name'       => $name,
        'albumCount' => count($albums),
        'album'      => array_map('album_entry', $albums),
    ]]);
}

case 'getAlbum': {
    $album = decode_album_id((string)($P['id'] ?? ''));
    if ($album === null) fail_sub(70, 'Album not found.');
    [$artist, $provider] = $album;
    $songs = album_songs($db, $artist, $provider);
    if ($songs === []) fail_sub(70, 'Album not found.');
    $entry = album_entry([
        'artist'    => $artist,
        'provider'  => $provider,
        'kind'      => $songs[0]['kind'],
        'songCount' => count($songs),
        'dur'       => array_sum(array_map(fn($s) => (float)$s['duration'], $songs)),
        'created'   => max(array_map(fn($s) => (int)$s['created_at'], $songs)),
    ]);
    $entry['song'] = array_map('song_entry', $songs);
    respond(['album' => $entry]);
}

case 'getSong': {
    $row = song_by_id($db, (string)($P['id'] ?? ''));
    if ($row === null) fail_sub(70, 'Song not found.');
    respond(['song' => song_entry($row)]);
}

case 'getAlbumList':
case 'getAlbumList2': {
    $size   = min(max((int)($P['size'] ?? 10), 1), 500);
    $offset = max((int)($P['offset'] ?? 0), 0);
    $albums = all_albums($db);
    switch ($P['type'] ?? 'alphabeticalByName') {
        case 'random':
            shuffle($albums);
            break;
        case 'newest':
        case 'recent':
            usort($albums, fn($a, $b) => $b['created'] <=> $a['created']);
            break;
        case 'alphabeticalByArtist':
            usort($albums, fn($a, $b) => strcasecmp($a['artist'], $b['artist']));
            break;
        case 'starred':
        case 'highest':
        case 'frequent':
        default:
            usort($albums, fn($a, $b) =>
                strcasecmp(album_name($a['provider'], $a['kind']), album_name($b['provider'], $b['kind']))
                ?: strcasecmp($a['artist'], $b['artist']));
    }
    $page = array_map('album_entry', array_slice($albums, $offset, $size));
    respond($method === 'getAlbumList2'
        ? ['albumList2' => ['album' => $page]]
        : ['albumList'  => ['album' => $page]]);
}

case 'getRandomSongs': {
    $size  = min(max((int)($P['size'] ?? 10), 1), 500);
    $where = AUDIO_WHERE;
    $params = [];
    if (($genre = strtolower((string)($P['genre'] ?? ''))) !== ''
        && in_array($genre, ['bgm', 'se'], true)) {
        $where = 'kind = :k';
        $params[':k'] = $genre;
    }
    $stmt = $db->prepare("SELECT * FROM assets WHERE {$where} ORDER BY RANDOM() LIMIT {$size}");
    $stmt->execute($params);
    respond(['randomSongs' => ['song' => array_map('song_entry', $stmt->fetchAll())]]);
}

case 'getSongsByGenre': {
    $genre = strtolower((string)($P['genre'] ?? ''));
    if (!in_array($genre, ['bgm', 'se'], true)) {
        respond(['songsByGenre' => []]);
    }
    $count  = min(max((int)($P['count'] ?? 10), 1), 500);
    $offset = max((int)($P['offset'] ?? 0), 0);
    $stmt = $db->prepare("SELECT * FROM assets WHERE kind = :k ORDER BY id LIMIT {$count} OFFSET {$offset}");
    $stmt->execute([':k' => $genre]);
    respond(['songsByGenre' => ['song' => array_map('song_entry', $stmt->fetchAll())]]);
}

case 'getGenres': {
    $rows = $db->query(
        'SELECT kind, COUNT(*) AS c, COUNT(DISTINCT ' . ARTIST_EXPR . " || '/' || provider) AS ac
         FROM assets WHERE " . AUDIO_WHERE . ' GROUP BY kind'
    )->fetchAll();
    respond(['genres' => ['genre' => array_map(fn($r) => [
        'songCount'  => (int)$r['c'],
        'albumCount' => (int)$r['ac'],
        '#text'      => strtoupper($r['kind']),
    ], $rows)]]);
}

case 'search2':
case 'search3': {
    $query = trim((string)($P['query'] ?? ''));
    $query = trim($query, '"*');   /* クライアントが付ける引用符・ワイルドカードを除去 */
    $artistCount  = min(max((int)($P['artistCount'] ?? 20), 0), 500);
    $artistOffset = max((int)($P['artistOffset'] ?? 0), 0);
    $albumCount   = min(max((int)($P['albumCount'] ?? 20), 0), 500);
    $albumOffset  = max((int)($P['albumOffset'] ?? 0), 0);
    $songCount    = min(max((int)($P['songCount'] ?? 20), 0), 500);
    $songOffset   = max((int)($P['songOffset'] ?? 0), 0);

    $artists = array_values(array_filter(all_artists($db),
        fn($a) => $query === '' || mb_stripos($a['name'], $query, 0, 'UTF-8') !== false));
    $albums = array_values(array_filter(all_albums($db),
        fn($a) => $query === ''
            || mb_stripos($a['artist'], $query, 0, 'UTF-8') !== false
            || mb_stripos(album_name($a['provider'], $a['kind']), $query, 0, 'UTF-8') !== false));

    if ($query === '') {
        $stmt = $db->prepare('SELECT * FROM assets WHERE ' . AUDIO_WHERE .
                             " ORDER BY id DESC LIMIT {$songCount} OFFSET {$songOffset}");
        $stmt->execute();
    } else {
        $stmt = $db->prepare('SELECT * FROM assets WHERE ' . AUDIO_WHERE . '
                              AND (title LIKE :q OR tags LIKE :q OR author LIKE :q)
                              ORDER BY id DESC LIMIT ' . $songCount . ' OFFSET ' . $songOffset);
        $stmt->execute([':q' => '%' . $query . '%']);
    }
    $result = [
        'artist' => array_map('artist_entry', array_slice($artists, $artistOffset, $artistCount)),
        'album'  => array_map('album_entry',  array_slice($albums,  $albumOffset,  $albumCount)),
        'song'   => array_map('song_entry',  $stmt->fetchAll()),
    ];
    respond($method === 'search3' ? ['searchResult3' => $result] : ['searchResult2' => $result]);
}

case 'stream':
case 'download': {
    $row = song_by_id($db, (string)($P['id'] ?? ''));
    if ($row === null) fail_sub(70, 'Song not found.');
    serve_media($row['preview']);
}

case 'getCoverArt': {
    $id = (string)($P['id'] ?? '');
    $thumb = '';
    if (ctype_digit($id)) {
        $row = song_by_id($db, $id);
        $thumb = $row['thumb'] ?? '';
    } elseif (($album = decode_album_id($id)) !== null) {
        $stmt = $db->prepare('SELECT thumb FROM assets WHERE ' . AUDIO_WHERE . '
                              AND ' . ARTIST_EXPR . " = :a AND provider = :p AND thumb != '' LIMIT 1");
        $stmt->execute([':a' => $album[0], ':p' => $album[1]]);
        $thumb = (string)$stmt->fetchColumn();
    } elseif (str_starts_with($id, 'ar-')) {
        $stmt = $db->prepare('SELECT thumb FROM assets WHERE ' . AUDIO_WHERE . '
                              AND ' . ARTIST_EXPR . " = :a AND thumb != '' LIMIT 1");
        $stmt->execute([':a' => b64d(substr($id, 3))]);
        $thumb = (string)$stmt->fetchColumn();
    }
    if ($thumb === '') fail_sub(70, 'Cover art not found.');
    serve_media($thumb);
}

/* ---- スタブ (クライアントの互換性のために ok を返すだけのもの) ------------- */

case 'getArtistInfo':
    respond(['artistInfo' => []]);
case 'getArtistInfo2':
    respond(['artistInfo2' => []]);
case 'getAlbumInfo':
case 'getAlbumInfo2':
    respond(['albumInfo' => []]);
case 'getTopSongs':
    respond(['topSongs' => []]);
case 'getSimilarSongs':
    respond(['similarSongs' => []]);
case 'getSimilarSongs2':
    respond(['similarSongs2' => []]);
case 'getLyrics':
    respond(['lyrics' => []]);
case 'getPlaylists':
    respond(['playlists' => []]);
case 'getPlaylist':
    fail_sub(70, 'Playlist not found.');
case 'getPodcasts':
    respond(['podcasts' => []]);
case 'getInternetRadioStations':
    respond(['internetRadioStations' => []]);
case 'getBookmarks':
    respond(['bookmarks' => []]);
case 'getShares':
    respond(['shares' => []]);
case 'getStarred':
    respond(['starred' => []]);
case 'getStarred2':
    respond(['starred2' => []]);
case 'getNowPlaying':
    respond(['nowPlaying' => []]);
case 'getPlayQueue':
    respond([]);
case 'savePlayQueue':
case 'scrobble':
case 'star':
case 'unstar':
case 'setRating':
    respond([]);
case 'getScanStatus':
case 'startScan': {
    $count = (int)$db->query('SELECT COUNT(*) FROM assets WHERE ' . AUDIO_WHERE)->fetchColumn();
    respond(['scanStatus' => ['scanning' => false, 'count' => $count]]);
}
case 'getUser':
    respond(['user' => [
        'username'            => (string)($P['u'] ?? ''),
        'scrobblingEnabled'   => false,
        'adminRole'           => false,
        'settingsRole'        => false,
        'downloadRole'        => true,
        'uploadRole'          => false,
        'playlistRole'        => false,
        'coverArtRole'        => false,
        'commentRole'         => false,
        'podcastRole'         => false,
        'streamRole'          => true,
        'jukeboxRole'         => false,
        'shareRole'           => false,
        'videoConversionRole' => false,
        'folder'              => [['#text' => 1]],
    ]]);
case 'getAvatar':
    fail_sub(70, 'Avatar not found.');

default:
    fail_sub(0, "Method not implemented: {$method}");
}
