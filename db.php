<?php
/* Auriga Assets — ローカル素材 DB (SQLite / PDO)
   data/assets.db に 1 テーブル (assets) を持つ。
   XAMPP 標準で有効な pdo_sqlite を使う (sqlite3 拡張は不要)。
   thumb / preview はアップロードした相対パス (uploads/...) か外部 URL のどちらか。 */

const ASSET_KINDS = ['bgm', 'se', 'image', 'video'];

function assets_db(): PDO
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
        /* DB ファイルを Web から直接ダウンロードされないようにする (Apache) */
        file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    $db = new PDO('sqlite:' . $dir . '/assets.db', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 3,
    ]);
    $db->exec('
        CREATE TABLE IF NOT EXISTS assets (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            kind       TEXT    NOT NULL,             -- bgm | se | image | video
            provider   TEXT    NOT NULL DEFAULT "",  -- 割り当て先プロバイダー id (providers.php)
            title      TEXT    NOT NULL,
            author     TEXT    NOT NULL DEFAULT "",
            tags       TEXT    NOT NULL DEFAULT "",  -- 空白区切りの検索用キーワード
            description TEXT   NOT NULL DEFAULT "",  -- 詳細情報 (例: 公開年月 | イメージ | ジャンル | 長さ\n使用楽器：…)
            thumb      TEXT    NOT NULL DEFAULT "",  -- サムネイル (uploads/... または URL)
            preview    TEXT    NOT NULL DEFAULT "",  -- 素材本体 (uploads/... または URL)
            page_url   TEXT    NOT NULL DEFAULT "",  -- 配布元ページ (任意)
            duration   REAL    NOT NULL DEFAULT 0,   -- 秒 (音声・動画のみ)
            created_at INTEGER NOT NULL
        )');

    /* 旧スキーマからのマイグレーション (足りない列を追加する) */
    $cols = array_column($db->query('PRAGMA table_info(assets)')->fetchAll(), 'name');
    if (!in_array('provider', $cols, true)) {
        $db->exec('ALTER TABLE assets ADD COLUMN provider TEXT NOT NULL DEFAULT ""');
    }
    if (!in_array('description', $cols, true)) {
        $db->exec('ALTER TABLE assets ADD COLUMN description TEXT NOT NULL DEFAULT ""');
    }
    return $db;
}
