<?php
/* Auriga Assets — ローカル素材 DB 登録画面
   素材はファイルアップロード (uploads/ に保存) か URL 指定のどちらかで登録する。
   下部に登録済み一覧 (削除ボタン付き)。追加/削除は PRG (redirect) で二重送信を防ぐ。
   注意: 認証は無いので、公開サーバーに置く場合は Basic 認証などで保護すること。 */
require __DIR__ . '/db.php';

/* 割り当て可能なプロバイダー (検索画面のピルと共通の定義) */
$PROVIDERS = require __DIR__ . '/providers.php';

/* 一覧のチップ表示用: kind → provider id → ラベル */
$PROVIDER_LABELS = [];
foreach ($PROVIDERS as $k => $list) {
    foreach ($list as $p) $PROVIDER_LABELS[$k][$p['id']] = $p['label'];
}

const KIND_LABELS = ['bgm' => 'BGM', 'se' => 'SE (効果音)', 'image' => '画像', 'video' => '動画'];
const KIND_ICONS  = ['bgm' => 'music', 'se' => 'wave-sine', 'image' => 'photo', 'video' => 'video'];

const UPLOAD_EXTS = ['mp3','wav','ogg','m4a','flac','mp4','webm','mov','jpg','jpeg','png','gif','webp'];

function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES); }

/* アップロードを uploads/ に保存して相対パスを返す。未指定は null、失敗は例外 */
function save_upload(string $field): ?string
{
    $f = $_FILES[$field] ?? null;
    if ($f === null || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('アップロードに失敗しました (エラー ' . $f['error'] . ')');

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_EXTS, true)) {
        throw new RuntimeException('対応していないファイル形式です: .' . $ext);
    }
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('ファイルを保存できませんでした');
    }
    return 'uploads/' . $name;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db     = assets_db();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT thumb, preview FROM assets WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            /* アップロード済みファイルも一緒に消す (URL 登録はそのまま) */
            foreach (['thumb', 'preview'] as $col) {
                if (str_starts_with($row[$col], 'uploads/')) @unlink(__DIR__ . '/' . $row[$col]);
            }
            $db->prepare('DELETE FROM assets WHERE id = :id')->execute([':id' => $id]);
        }
        header('Location: admin.php?deleted=1');
        exit;
    }

    if ($action === 'add') {
        $kind  = $_POST['kind'] ?? '';
        $title = trim($_POST['title'] ?? '');
        try {
            if (!in_array($kind, ASSET_KINDS, true)) throw new RuntimeException('種別を選択してください');
            if ($title === '')                       throw new RuntimeException('タイトルを入力してください');

            $providerId = $_POST['provider'] ?? '';
            if (!in_array($providerId, array_column($PROVIDERS[$kind], 'id'), true)) {
                throw new RuntimeException('割り当て先プロバイダーを選択してください');
            }

            $preview = save_upload('preview_file') ?? trim($_POST['preview_url'] ?? '');
            $thumb   = save_upload('thumb_file')   ?? trim($_POST['thumb_url'] ?? '');
            if ($preview === '' && $thumb === '') {
                throw new RuntimeException('素材ファイルをアップロードするか、素材 URL を入力してください');
            }

            $stmt = $db->prepare('INSERT INTO assets (kind, provider, title, author, tags, thumb, preview, page_url, duration, created_at)
                                  VALUES (:kind, :provider, :title, :author, :tags, :thumb, :preview, :page_url, :duration, :now)');
            $stmt->execute([
                ':kind'     => $kind,
                ':provider' => $providerId,
                ':title'    => $title,
                ':author'   => trim($_POST['author'] ?? ''),
                ':tags'     => trim($_POST['tags'] ?? ''),
                ':thumb'    => $thumb,
                ':preview'  => $preview,
                ':page_url' => trim($_POST['page_url'] ?? ''),
                ':duration' => (float)($_POST['duration'] ?? 0),
                ':now'      => time(),
            ]);

            header('Location: admin.php?ok=1');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

/* 登録済み一覧 (新着順) */
$rows = assets_db()->query('SELECT * FROM assets ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>素材の登録 | Auriga Assets</title>
<meta name="theme-color" content="#F6F2FB">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<style>
/* ===== Material 3 ライト (index.php と共通のトークン) ===== */
:root{
  --bg0:#F6F2FB;--bg1:#FFFBFE;--bg2:#F3EDF7;--bg3:#E7E0EC;
  --border:#CAC4D0;--border2:#79747E;
  --txt1:#1C1B1F;--txt2:#49454F;--txt3:#79747E;
  --primary:#6750A4;--primary-dim:#EADDFF;
  --error:#B3261E;--error-dim:#F9DEDC;
  --ok:#1F7A1F;--ok-dim:#DCF2DC;
  --shadow:0 4px 24px rgba(0,0,0,.08);
  --radius:16px;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

body{
  font-family:Roboto,'Noto Sans JP','Hiragino Sans',Meiryo,system-ui,sans-serif;
  background:var(--bg0);
  color:var(--txt1);
  font-size:14px;
  -webkit-font-smoothing:antialiased;
  min-height:100dvh;
}

a{color:inherit}

/* ===== トップバー ===== */
.topbar{
  position:sticky;top:0;z-index:50;height:56px;
  display:flex;align-items:center;gap:12px;
  padding:0 20px;
  background:var(--bg1);
  border-bottom:1px solid var(--border);
}
.back-btn{
  display:inline-flex;align-items:center;justify-content:center;
  width:36px;height:36px;border-radius:50%;
  color:var(--txt2);font-size:20px;text-decoration:none;
  transition:background .15s;
}
.back-btn:hover{background:var(--bg2)}
.topbar-title{font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px}
.topbar-title i{color:var(--primary)}

/* ===== コンテナ ===== */
.container{max-width:900px;margin:0 auto;padding:28px 24px 64px}

.section-title{
  display:flex;align-items:center;gap:8px;
  font-size:16px;font-weight:700;margin:32px 4px 12px;
}
.section-title i{color:var(--primary)}
.section-title .count{font-size:12px;font-weight:500;color:var(--txt3)}

/* ===== アラート ===== */
.alert{
  display:flex;align-items:center;gap:10px;
  border-radius:12px;padding:12px 16px;margin-bottom:16px;
  font-size:13px;
}
.alert i{font-size:18px;flex-shrink:0}
.alert.is-error{background:var(--error-dim);color:var(--error)}
.alert.is-ok{background:var(--ok-dim);color:var(--ok)}

/* ===== フォームカード ===== */
.form-card{
  background:var(--bg1);border:1px solid var(--border);border-radius:24px;
  padding:24px;box-shadow:var(--shadow);
}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.field{display:flex;flex-direction:column;gap:6px;min-width:0}
.field.span2{grid-column:span 2}
.field label{font-size:12px;font-weight:600;color:var(--txt2)}
.field label small{font-weight:400;color:var(--txt3)}
.field input[type=text],.field input[type=url],.field input[type=number],.field select{
  height:42px;padding:0 14px;
  border:1px solid var(--border);border-radius:12px;
  background:var(--bg1);color:var(--txt1);
  font-family:inherit;font-size:14px;outline:none;
  transition:border-color .15s,box-shadow .15s;
}
.field input:focus,.field select:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-dim)}
.field input[type=file]{
  padding:9px 10px;border:1px dashed var(--border);border-radius:12px;
  background:var(--bg0);font-family:inherit;font-size:13px;color:var(--txt2);
}
.field input[type=file]::file-selector-button{
  margin-right:10px;padding:6px 14px;border:none;border-radius:999px;
  background:var(--primary-dim);color:var(--primary);
  font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;
}

/* 種別ラジオピル (index.php の provider-tab と同じ見た目) */
.kind-pills{display:flex;gap:8px;flex-wrap:wrap}
.kind-pills input{position:absolute;opacity:0;pointer-events:none}
.kind-pills label{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:999px;
  background:var(--bg1);border:1px solid var(--border);
  font-size:13px;color:var(--txt2);cursor:pointer;
  transition:background .15s;
}
.kind-pills label:hover{background:var(--bg2)}
.kind-pills label i{font-size:16px}
.kind-pills input:checked + label{
  background:var(--primary-dim);color:var(--primary);
  border-color:transparent;font-weight:600;
}
.kind-pills input:focus-visible + label{box-shadow:0 0 0 3px var(--primary-dim)}

.form-divider{
  grid-column:span 2;display:flex;align-items:center;gap:10px;
  font-size:12px;color:var(--txt3);margin-top:4px;
}
.form-divider::before,.form-divider::after{content:'';flex:1;height:1px;background:var(--border)}

.form-actions{display:flex;justify-content:flex-end;margin-top:20px}
.m-button{
  display:inline-flex;align-items:center;gap:7px;
  height:40px;padding:0 24px;border:none;border-radius:999px;
  background:var(--primary);color:#fff;
  font-size:13px;font-weight:500;font-family:inherit;
  text-decoration:none;cursor:pointer;transition:filter .15s;
}
.m-button:hover{filter:brightness(1.1)}
.m-button i{font-size:17px}

/* ===== 一覧 ===== */
.asset-table{display:flex;flex-direction:column;gap:10px}
.asset-row{
  display:flex;align-items:center;gap:14px;
  background:var(--bg1);border:1px solid var(--border);border-radius:var(--radius);
  padding:10px 14px;
}
.asset-row-thumb{
  width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0;
  background:var(--bg3);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--txt3);font-size:22px;overflow:hidden;
}
.asset-row-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.kind-chip{
  display:inline-flex;align-items:center;gap:4px;
  padding:2px 10px;border-radius:999px;flex-shrink:0;
  background:var(--primary-dim);color:var(--primary);
  font-size:11px;font-weight:600;
}
.kind-chip i{font-size:13px}
/* 割り当て先プロバイダーのチップ (種別チップの隣) */
.provider-chip{background:var(--bg2);color:var(--txt2);border:1px solid var(--border)}
.asset-row-info{min-width:0;flex:1}
.asset-row-title{font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.asset-row-sub{font-size:12px;color:var(--txt3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px}
.asset-row-date{font-size:12px;color:var(--txt3);flex-shrink:0}
.icon-btn{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:50%;border:none;flex-shrink:0;
  background:none;color:var(--txt3);font-size:17px;cursor:pointer;
  text-decoration:none;transition:background .15s,color .15s;
}
.icon-btn:hover{background:var(--bg2);color:var(--primary)}
.icon-btn.is-danger:hover{background:var(--error-dim);color:var(--error)}

.placeholder{color:var(--txt3);font-size:13px;padding:8px 2px;display:flex;align-items:center;gap:8px}
.placeholder i{font-size:18px}

/* ===== レスポンシブ ===== */
@media (max-width:640px){
  .container{padding:20px 14px 48px}
  .form-grid{grid-template-columns:1fr}
  .field.span2,.form-divider{grid-column:span 1}
  .asset-row-date{display:none}
}
</style>
</head>
<body>

<header class="topbar">
  <a class="back-btn" href="index.php" title="検索に戻る" aria-label="検索に戻る"><i class="ti ti-arrow-left" aria-hidden="true"></i></a>
  <div class="topbar-title"><i class="ti ti-database-plus" aria-hidden="true"></i> 素材の登録 (ローカルDB)</div>
</header>

<div class="container">

<?php if ($error !== ''): ?>
  <div class="alert is-error" role="alert"><i class="ti ti-alert-circle" aria-hidden="true"></i> <?= h($error) ?></div>
<?php elseif (isset($_GET['ok'])): ?>
  <div class="alert is-ok" role="status"><i class="ti ti-circle-check" aria-hidden="true"></i> 素材を登録しました</div>
<?php elseif (isset($_GET['deleted'])): ?>
  <div class="alert is-ok" role="status"><i class="ti ti-circle-check" aria-hidden="true"></i> 素材を削除しました</div>
<?php endif; ?>

  <form class="form-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <div class="form-grid">

      <div class="field span2">
        <label>種別 *</label>
        <div class="kind-pills">
<?php foreach (KIND_LABELS as $k => $label): ?>
          <input type="radio" name="kind" value="<?= $k ?>" id="kind-<?= $k ?>"
                 <?= ($_POST['kind'] ?? 'bgm') === $k ? 'checked' : '' ?>>
          <label for="kind-<?= $k ?>"><i class="ti ti-<?= KIND_ICONS[$k] ?>" aria-hidden="true"></i> <?= $label ?></label>
<?php endforeach; ?>
        </div>
      </div>

      <div class="field span2">
        <label for="f-provider">割り当て先プロバイダー * <small>(検索画面でこのプロバイダーの一覧に表示されます)</small></label>
        <select id="f-provider" name="provider">
<?php $formKind = in_array($_POST['kind'] ?? '', ASSET_KINDS, true) ? $_POST['kind'] : 'bgm'; ?>
<?php foreach ($PROVIDERS[$formKind] as $p): ?>
          <option value="<?= h($p['id']) ?>" <?= ($_POST['provider'] ?? '') === $p['id'] ? 'selected' : '' ?>><?= h($p['label']) ?></option>
<?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="f-title">タイトル *</label>
        <input type="text" id="f-title" name="title" required value="<?= h($_POST['title'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="f-author">作者 <small>(任意)</small></label>
        <input type="text" id="f-author" name="author" value="<?= h($_POST['author'] ?? '') ?>">
      </div>

      <div class="field span2">
        <label for="f-tags">タグ <small>(空白区切り、検索キーワードになります)</small></label>
        <input type="text" id="f-tags" name="tags" placeholder="例: ピアノ 切ない ループ" value="<?= h($_POST['tags'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="f-preview-file">素材ファイル <small>(音声 / 動画 / 画像をアップロード)</small></label>
        <input type="file" id="f-preview-file" name="preview_file"
               accept=".mp3,.wav,.ogg,.m4a,.flac,.mp4,.webm,.mov,.jpg,.jpeg,.png,.gif,.webp">
      </div>
      <div class="field">
        <label for="f-thumb-file">サムネイル <small>(任意、画像)</small></label>
        <input type="file" id="f-thumb-file" name="thumb_file" accept=".jpg,.jpeg,.png,.gif,.webp">
      </div>

      <div class="form-divider">またはファイルの代わりに URL で指定</div>

      <div class="field">
        <label for="f-preview-url">素材 URL</label>
        <input type="url" id="f-preview-url" name="preview_url" placeholder="https://…/sample.mp3" value="<?= h($_POST['preview_url'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="f-thumb-url">サムネイル URL</label>
        <input type="url" id="f-thumb-url" name="thumb_url" placeholder="https://…/thumb.jpg" value="<?= h($_POST['thumb_url'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="f-page">配布元ページ URL <small>(任意)</small></label>
        <input type="url" id="f-page" name="page_url" value="<?= h($_POST['page_url'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="f-duration">長さ (秒) <small>(音声・動画。ファイル選択時は自動入力)</small></label>
        <input type="number" id="f-duration" name="duration" min="0" step="0.1" value="<?= h($_POST['duration'] ?? '') ?>">
      </div>

    </div>
    <div class="form-actions">
      <button class="m-button" type="submit"><i class="ti ti-database-plus" aria-hidden="true"></i> 登録する</button>
    </div>
  </form>

  <h2 class="section-title">
    <i class="ti ti-list-details" aria-hidden="true"></i> 登録済み素材
    <span class="count"><?= count($rows) ?> 件</span>
  </h2>

<?php if (count($rows) === 0): ?>
  <p class="placeholder"><i class="ti ti-database-off" aria-hidden="true"></i> まだ素材がありません</p>
<?php else: ?>
  <div class="asset-table">
<?php foreach ($rows as $r):
    $thumbSrc = $r['thumb'] !== '' ? $r['thumb'] : ($r['kind'] === 'image' ? $r['preview'] : ''); ?>
    <div class="asset-row">
      <div class="asset-row-thumb">
<?php if ($thumbSrc !== ''): ?>
        <img src="<?= h($thumbSrc) ?>" alt="" loading="lazy">
<?php else: ?>
        <i class="ti ti-<?= KIND_ICONS[$r['kind']] ?? 'file' ?>" aria-hidden="true"></i>
<?php endif; ?>
      </div>
      <span class="kind-chip"><i class="ti ti-<?= KIND_ICONS[$r['kind']] ?? 'file' ?>" aria-hidden="true"></i> <?= KIND_LABELS[$r['kind']] ?? h($r['kind']) ?></span>
      <span class="kind-chip provider-chip"><?= h($PROVIDER_LABELS[$r['kind']][$r['provider']] ?? '未割り当て') ?></span>
      <div class="asset-row-info">
        <div class="asset-row-title"><?= h($r['title']) ?></div>
        <div class="asset-row-sub">
          <?= $r['author'] !== '' ? h($r['author']) . ' · ' : '' ?><?= $r['tags'] !== '' ? h($r['tags']) : 'タグなし' ?>
        </div>
      </div>
      <span class="asset-row-date"><?= date('Y/m/d', $r['created_at']) ?></span>
<?php if ($r['preview'] !== ''): ?>
      <a class="icon-btn" href="<?= h($r['preview']) ?>" target="_blank" rel="noopener" title="素材を開く"><i class="ti ti-external-link" aria-hidden="true"></i></a>
<?php endif; ?>
      <form method="post" onsubmit="return confirm('「<?= h($r['title']) ?>」を削除しますか？')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="icon-btn is-danger" type="submit" title="削除" aria-label="削除"><i class="ti ti-trash" aria-hidden="true"></i></button>
      </form>
    </div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

</div>

<script>
/* 種別を切り替えたら、割り当て先プロバイダーの選択肢をその種別のものに入れ替える */
const PROVIDERS_BY_KIND = <?= json_encode(
    array_map(fn($list) => array_map(fn($p) => ['id' => $p['id'], 'label' => $p['label']], $list), $PROVIDERS),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function fillProviderOptions(kind) {
  const sel = document.getElementById('f-provider');
  sel.replaceChildren(...PROVIDERS_BY_KIND[kind].map(p => {
    const o = document.createElement('option');
    o.value = p.id;
    o.textContent = p.label;
    return o;
  }));
}
document.querySelectorAll('input[name=kind]').forEach(r =>
  r.addEventListener('change', () => fillProviderOptions(r.value)));

/* 素材ファイルに音声/動画を選んだら、長さ (秒) を自動入力する */
document.getElementById('f-preview-file').addEventListener('change', e => {
  const f = e.target.files[0];
  if (!f || !/^(audio|video)\//.test(f.type)) return;
  const el = document.createElement(f.type.startsWith('audio') ? 'audio' : 'video');
  el.preload = 'metadata';
  el.src = URL.createObjectURL(f);
  el.onloadedmetadata = () => {
    document.getElementById('f-duration').value = Math.round(el.duration * 10) / 10;
    URL.revokeObjectURL(el.src);
  };
});
</script>
</body>
</html>
