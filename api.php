<?php
/* Auriga Assets — 検索プロキシ
   ブラウザから API キーを隠すため、キーが必要なプロバイダーへの検索は
   すべてこのエンドポイントを経由する。

   GET api.php?provider=pixabay-image&q=猫
   → { "items": [ { type, title, author, thumb, full, preview, duration, pageUrl } ] }

   provider=local はさらに offset / limit / sort を受け付け、
   { "items": [...], "total": 全件数, "offset": .., "limit": .., "hasMore": bool }
   を返す (index.php のスクロール追従の追加読み込みで使う)。

   type: image | video | audio
     image: thumb (一覧用) / full (原寸ページ用 URL)
     video: thumb + preview (mp4 直リンク、クリックでインライン再生)
     audio: preview (mp3 直リンク、再生バーで使用)
   失敗時は { "error": "no_key" | "http" | "unknown_provider", ... } を返す。 */

header('Content-Type: application/json; charset=utf-8');

$KEYS     = require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

$provider = $_GET['provider'] ?? '';
$q        = trim($_GET['q'] ?? '');

/* local (SQLite) はキーワード空でも全件一覧を返すので除外する */
if ($q === '' && $provider !== 'local') {
    echo json_encode(['items' => []]);
    exit;
}

/* HTTP GET (curl 優先、無ければ file_get_contents) */
function http_get(string $url, array $headers = []): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => 'AurigaAssets/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? $body : null;
    }
    $ctx = stream_context_create(['http' => [
        'header'  => implode("\r\n", array_merge($headers, ['User-Agent: AurigaAssets/1.0'])),
        'timeout' => 15,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? null : $body;
}

function fail(string $error, int $status = 502): never
{
    http_response_code($status);
    echo json_encode(['error' => $error]);
    exit;
}

function need_key(array $keys, string $name): string
{
    if (($keys[$name] ?? '') === '') fail('no_key', 200);
    return $keys[$name];
}

function fetch_json(string $url, array $headers = []): array
{
    $body = http_get($url, $headers);
    if ($body === null) fail('http');
    $data = json_decode($body, true);
    if (!is_array($data)) fail('http');
    return $data;
}

$eq = rawurlencode($q);

/* items に添えて返す付加情報 (local のページング情報など) */
$meta = [];

switch ($provider) {

case 'local': {
    /* ローカル素材 DB (SQLite)。kind=bgm|se|image|video と割り当て先プロバイダー
       (target=providers.php の id) で絞り込み、キーワードはタイトル・タグ・作者を
       部分一致で検索する。結果は割り当て先プロバイダーの一覧の先頭に描画される
       (local: true が目印)。
       件数が多いので offset / limit で切り出し、index.php がスクロールに応じて
       次のページを取りに来る。並び替えは DB 全件に対してここで行う
       (ページごとにクライアント側で並べても全体の並び順にはならないため) */
    $kind   = $_GET['kind'] ?? '';
    $target = $_GET['target'] ?? '';   /* 空なら全プロバイダー横断 (「すべて」ピル用) */
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    if (!in_array($kind, ASSET_KINDS, true)) fail('bad_kind', 400);

    /* 並び順 (index.php の SORT_OPTIONS と対応)。
       投稿日時は説明文の先頭にある配布元の公開日を数値キー (YYYYMMDD) にする。
       Amacha は「2017.02」の年月、DOVA は「2009-03-01」の年月日で、
       日の位置が数字でない前者は CAST が 0 になり月までの比較になる。
       日付や再生時間を持たない素材は、どの並び順でも末尾に回す */
    $dateKey = 'CAST(substr(description,1,4) AS INTEGER) * 10000 +
                CAST(substr(description,6,2) AS INTEGER) * 100 +
                CAST(substr(description,9,2) AS INTEGER)';
    $hasDate = "description GLOB '[0-9][0-9][0-9][0-9][-./][0-9][0-9]*'";
    $ORDERS  = [
        'default'   => 'id DESC',
        'date-new'  => "({$hasDate}) DESC, ({$dateKey}) DESC, title COLLATE NOCASE",
        'date-old'  => "({$hasDate}) DESC, ({$dateKey}) ASC, title COLLATE NOCASE",
        'dur-long'  => '(duration > 0) DESC, duration DESC, title COLLATE NOCASE',
        'dur-short' => '(duration > 0) DESC, duration ASC, title COLLATE NOCASE',
    ];
    $order = $ORDERS[$_GET['sort'] ?? ''] ?? $ORDERS['default'];

    $db     = assets_db();
    $where  = 'kind = :k';
    $params = [':k' => $kind];
    if ($target !== '') {
        $where .= ' AND provider = :p';
        $params[':p'] = $target;
    }
    if ($q !== '') {
        $where .= ' AND (title LIKE :q OR tags LIKE :q OR author LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    /* 総件数 (UI の「n 件」表示と、追加読み込みの終了判定に使う) */
    $cnt = $db->prepare("SELECT COUNT(*) FROM assets WHERE {$where}");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM assets WHERE {$where} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $stmt->execute($params);
    /* hasMore は返却件数ではなく DB 上の位置で判定する
       (サムネイルも本体も無い行は下で除かれ、返却件数が limit より減りうるため) */
    $meta = ['total'  => $total, 'offset' => $offset, 'limit' => $limit,
             'hasMore' => $offset + $limit < $total];
    $type  = $kind === 'image' ? 'image' : ($kind === 'video' ? 'video' : 'audio');
    $items = [];
    foreach ($stmt as $row) {
        $items[] = [
            'type'        => $type,
            'local'       => true,
            'provider'    => $row['provider'],
            'title'       => $row['title'],
            'description' => $row['description'],
            'author'      => $row['author'],
            'thumb'    => $row['thumb'] !== '' ? $row['thumb'] : ($type === 'image' ? $row['preview'] : ''),
            'full'     => $row['preview'] !== '' ? $row['preview'] : $row['thumb'],
            'preview'  => $row['preview'],
            'duration' => $row['duration'],
            'pageUrl'  => $row['page_url'] !== '' ? $row['page_url'] : $row['preview'],
            'trackTotal'  => (int)($row['track_total'] ?? 0),
            'ncId'        => $row['nc_id'] ?? '',
            'commonsUrl'  => $row['commons_url'] ?? '',
            'downloadUrl' => $row['download_url'] ?? '',
        ];
    }
    break;
}

case 'pixabay-image': {
    $key  = need_key($KEYS, 'pixabay');
    $data = fetch_json("https://pixabay.com/api/?key={$key}&q={$eq}&per_page=30&safesearch=true&lang=ja");
    $items = array_map(fn($h) => [
        'type'    => 'image',
        'title'   => $h['tags'] ?? '',
        'author'  => $h['user'] ?? '',
        'thumb'   => $h['webformatURL'] ?? ($h['previewURL'] ?? ''),
        'full'    => $h['largeImageURL'] ?? '',
        'pageUrl' => $h['pageURL'] ?? '',
    ], $data['hits'] ?? []);
    break;
}

case 'pixabay-video': {
    $key  = need_key($KEYS, 'pixabay');
    $data = fetch_json("https://pixabay.com/api/videos/?key={$key}&q={$eq}&per_page=24&safesearch=true&lang=ja");
    $items = array_map(function ($h) {
        $v = $h['videos']['small'] ?? ($h['videos']['tiny'] ?? []);
        return [
            'type'     => 'video',
            'title'    => $h['tags'] ?? '',
            'author'   => $h['user'] ?? '',
            'thumb'    => $v['thumbnail'] ?? '',
            'preview'  => $v['url'] ?? '',
            'duration' => $h['duration'] ?? 0,
            'pageUrl'  => $h['pageURL'] ?? '',
        ];
    }, $data['hits'] ?? []);
    break;
}

case 'pexels-image': {
    $key  = need_key($KEYS, 'pexels');
    $data = fetch_json("https://api.pexels.com/v1/search?query={$eq}&per_page=30&locale=ja-JP",
                       ["Authorization: {$key}"]);
    $items = array_map(fn($p) => [
        'type'    => 'image',
        'title'   => $p['alt'] ?? '',
        'author'  => $p['photographer'] ?? '',
        'thumb'   => $p['src']['medium'] ?? '',
        'full'    => $p['src']['large2x'] ?? '',
        'pageUrl' => $p['url'] ?? '',
    ], $data['photos'] ?? []);
    break;
}

case 'pexels-video': {
    $key  = need_key($KEYS, 'pexels');
    $data = fetch_json("https://api.pexels.com/videos/search?query={$eq}&per_page=24&locale=ja-JP",
                       ["Authorization: {$key}"]);
    $items = array_map(function ($v) {
        /* video_files から一番軽い sd/hd を選んでプレビューに使う */
        $files = $v['video_files'] ?? [];
        usort($files, fn($a, $b) => ($a['width'] ?? PHP_INT_MAX) <=> ($b['width'] ?? PHP_INT_MAX));
        $preview = '';
        foreach ($files as $f) {
            if (($f['file_type'] ?? '') === 'video/mp4') { $preview = $f['link'] ?? ''; break; }
        }
        return [
            'type'     => 'video',
            'title'    => '',
            'author'   => $v['user']['name'] ?? '',
            'thumb'    => $v['image'] ?? '',
            'preview'  => $preview,
            'duration' => $v['duration'] ?? 0,
            'pageUrl'  => $v['url'] ?? '',
        ];
    }, $data['videos'] ?? []);
    break;
}

case 'unsplash': {
    $key  = need_key($KEYS, 'unsplash');
    $data = fetch_json("https://api.unsplash.com/search/photos?query={$eq}&per_page=30&client_id={$key}");
    $items = array_map(fn($r) => [
        'type'    => 'image',
        'title'   => $r['alt_description'] ?? '',
        'author'  => $r['user']['name'] ?? '',
        'thumb'   => $r['urls']['small'] ?? '',
        'full'    => $r['urls']['regular'] ?? '',
        'pageUrl' => $r['links']['html'] ?? '',
    ], $data['results'] ?? []);
    break;
}

case 'jamendo': {
    $key  = need_key($KEYS, 'jamendo');
    $data = fetch_json("https://api.jamendo.com/v3.0/tracks/?client_id={$key}&format=json&limit=30&search={$eq}&audioformat=mp32&include=licenses");
    $items = array_map(fn($t) => [
        'type'     => 'audio',
        'title'    => $t['name'] ?? '',
        'author'   => $t['artist_name'] ?? '',
        'thumb'    => $t['image'] ?? '',
        'preview'  => $t['audio'] ?? '',
        'duration' => $t['duration'] ?? 0,
        'pageUrl'  => $t['shareurl'] ?? '',
    ], $data['results'] ?? []);
    break;
}

case 'freesound': {
    $key  = need_key($KEYS, 'freesound');
    $data = fetch_json("https://freesound.org/apiv2/search/text/?query={$eq}&token={$key}&page_size=30&fields=" .
                       rawurlencode('name,previews,duration,username,url,images'));
    $items = array_map(fn($s) => [
        'type'     => 'audio',
        'title'    => $s['name'] ?? '',
        'author'   => $s['username'] ?? '',
        'thumb'    => $s['images']['waveform_m'] ?? '',
        'preview'  => $s['previews']['preview-hq-mp3'] ?? ($s['previews']['preview-lq-mp3'] ?? ''),
        'duration' => $s['duration'] ?? 0,
        'pageUrl'  => $s['url'] ?? '',
    ], $data['results'] ?? []);
    break;
}

default:
    fail('unknown_provider', 400);
}

echo json_encode(['items' => array_values(array_filter($items, fn($i) => $i['thumb'] !== '' || $i['preview'] !== ''))] + $meta,
                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
