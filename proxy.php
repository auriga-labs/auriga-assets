<?php
/* Auriga Assets — メディア中継プロキシ (CORS / ホットリンク回避)
   ローカル DB に URL で登録したリモート素材は、配布元のホットリンク保護
   (Referer チェック) や CORS 制限でブラウザから直接再生できないことがある。
   このエンドポイントがサーバー側で取得してそのまま中継する。

   GET proxy.php?url=https://…/sample.mp3

   オープンプロキシ化 (SSRF・帯域の踏み台) を防ぐため、ローカル DB の
   preview / thumb に登録済みの URL だけを中継する。
   Range リクエストを転送するので再生バーのシークも効く。 */

require __DIR__ . '/db.php';

function deny(int $status, string $msg): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

$url   = $_GET['url'] ?? '';
$parts = parse_url($url);
if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || ($parts['host'] ?? '') === '') {
    deny(400, 'bad url');
}

/* DB に登録済みの URL のみ許可する */
$stmt = assets_db()->prepare('SELECT COUNT(*) FROM assets WHERE preview = :u OR thumb = :u');
$stmt->execute([':u' => $url]);
if ((int)$stmt->fetchColumn() === 0) {
    deny(403, 'url not registered');
}

set_time_limit(0);
while (ob_get_level() > 0) ob_end_clean();

/* ホットリンク保護対策: 配布元と同一オリジンの Referer を付けて取得する */
$reqHeaders = ['Referer: ' . strtolower($parts['scheme']) . '://' . $parts['host'] . '/'];
if (isset($_SERVER['HTTP_RANGE'])) {
    $reqHeaders[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
}

/* 中継するレスポンスヘッダー (メディア再生・シークに必要なものだけ) */
const PASS_HEADERS = ['content-type', 'content-length', 'content-range', 'accept-ranges'];

header('Access-Control-Allow-Origin: *');

if (function_exists('curl_init')) {
    $status      = 200;
    $respHeaders = [];
    $sentHeaders = false;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => $reqHeaders,
        CURLOPT_USERAGENT      => 'AurigaAssets/1.0',
        /* リダイレクトのたびにヘッダーを取り直し、最終レスポンスだけ中継する */
        CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$status, &$respHeaders) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
                $status      = (int)$m[1];
                $respHeaders = [];
            } elseif (($p = strpos($line, ':')) !== false) {
                $respHeaders[strtolower(trim(substr($line, 0, $p)))] = trim(substr($line, $p + 1));
            }
            return strlen($line);
        },
        /* 全体をメモリに溜めず、届いたチャンクからブラウザへ流す */
        CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$status, &$respHeaders, &$sentHeaders) {
            if (!$sentHeaders) {
                http_response_code($status);
                foreach (PASS_HEADERS as $h) {
                    if (isset($respHeaders[$h])) header(ucwords($h, '-') . ': ' . $respHeaders[$h]);
                }
                $sentHeaders = true;
            }
            echo $chunk;
            flush();
            return strlen($chunk);
        },
    ]);
    $ok = curl_exec($ch);
    curl_close($ch);
    if (!$sentHeaders) {
        /* 1 バイトも届かないまま終了 (接続失敗 or 空ボディ) */
        if ($ok === false) deny(502, 'fetch failed');
        http_response_code($status);
        foreach (PASS_HEADERS as $h) {
            if (isset($respHeaders[$h])) header(ucwords($h, '-') . ': ' . $respHeaders[$h]);
        }
    }
    exit;
}

/* curl 拡張が無い環境のフォールバック (リダイレクト・エラーボディも中継) */
$ctx = stream_context_create(['http' => [
    'header'          => implode("\r\n", array_merge($reqHeaders, ['User-Agent: AurigaAssets/1.0'])),
    'follow_location' => 1,
    'max_redirects'   => 5,
    'timeout'         => 30,
    'ignore_errors'   => true,
]]);
$fp = @fopen($url, 'rb', false, $ctx);
if ($fp === false) deny(502, 'fetch failed');

$status = 200;
foreach ($http_response_header ?? [] as $line) {
    if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
        $status = (int)$m[1];
        continue;
    }
    if (($p = strpos($line, ':')) !== false
        && in_array($name = strtolower(trim(substr($line, 0, $p))), PASS_HEADERS, true)) {
        header(ucwords($name, '-') . ': ' . trim(substr($line, $p + 1)));
    }
}
http_response_code($status);
fpassthru($fp);
fclose($fp);
