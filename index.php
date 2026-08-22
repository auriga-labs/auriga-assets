<?php
/* Auriga Assets — フリー素材検索
   BGM / SE / 画像 / 動画 の 4 タブ構成。デスクトップは左サイドバー、
   モバイルは下部固定バーで切り替える (amanetoki.com index.php と同じ構成)。
   各タブ内にプロバイダー選択ピルを持ち、
   - API 対応プロバイダー → api.php 経由でインライン検索結果を表示
   - API 非対応プロバイダー → 各サイトの検索ページを新しいタブで開く */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auriga Assets | フリー素材検索</title>
<meta name="description" content="BGM・効果音・画像・動画のフリー素材を横断検索。">
<meta name="theme-color" content="#F6F2FB">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap">

<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<style>
/* ===== Material 3 ライト (amanetoki.com と共通のトークン) ===== */
:root{
  --bg0:#F6F2FB;--bg1:#FFFBFE;--bg2:#F3EDF7;--bg3:#E7E0EC;
  --border:#CAC4D0;--border2:#79747E;
  --txt1:#1C1B1F;--txt2:#49454F;--txt3:#79747E;
  --primary:#6750A4;--primary-dim:#EADDFF;
  --shadow:0 4px 24px rgba(0,0,0,.08);
  --radius:16px;
  --bottom-nav-h:60px;
  --player-h:64px;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

html,body{height:100%}
body{
  font-family:Roboto,'Noto Sans JP','Hiragino Sans',Meiryo,system-ui,sans-serif;
  background:var(--bg0);
  color:var(--txt1);
  font-size:14px;
  -webkit-font-smoothing:antialiased;
}

a{color:inherit}

/* ===== レイアウト ===== */
.wrapper{display:flex;height:100dvh;overflow:hidden}

/* ===== サイドバー (デスクトップ) ===== */
.sidebar{
  width:240px;flex-shrink:0;
  background:var(--bg1);
  border-right:1px solid var(--border);
  padding:12px 0 16px;
  overflow-y:auto;
  display:flex;flex-direction:column;
}

.brand{
  display:flex;align-items:center;gap:12px;
  padding:14px 20px 20px;
  text-decoration:none;
}
.brand-logo{
  width:36px;height:36px;border-radius:12px;flex-shrink:0;
  background:var(--primary);color:#fff;
  display:flex;align-items:center;justify-content:center;font-size:20px;
}
.brand-name{font-size:17px;font-weight:700;letter-spacing:-.3px}
.brand-name small{display:block;font-size:11px;font-weight:500;color:var(--txt3);letter-spacing:0}

.nav-item{
  display:flex;align-items:center;gap:14px;
  margin:2px 12px;padding:11px 16px;
  border-radius:999px;
  font-size:14px;color:var(--txt2);
  text-decoration:none;cursor:pointer;
  transition:background .15s;
}
.nav-item:hover{background:var(--bg2);color:var(--txt1)}
.nav-item.active{background:var(--primary-dim);color:var(--primary);font-weight:600}
.nav-item i{font-size:21px;flex-shrink:0}

.sidebar-foot{
  margin-top:auto;padding:18px 24px 8px;
  font-size:12px;color:var(--txt3);line-height:1.7;
}

/* ===== メイン ===== */
.main{flex:1;min-width:0;display:flex;flex-direction:column}

/* ===== トップバー ===== */
.topbar{
  flex-shrink:0;height:56px;
  display:flex;align-items:center;gap:12px;
  padding:0 20px;
  background:var(--bg1);
  border-bottom:1px solid var(--border);
}
.mobile-brand{display:none;align-items:center;gap:10px;text-decoration:none;min-width:0}
.mobile-brand .brand-logo{width:30px;height:30px;font-size:17px;border-radius:10px}
.mobile-brand .brand-name{font-size:16px}
.topbar-title{font-size:16px;font-weight:600}

/* ===== パネル ===== */
.panel{display:none;flex:1;min-height:0;overflow-y:auto;padding:28px 24px 48px}
.panel.active{display:block}
.panel-inner{max-width:1100px;margin:0 auto}

.panel-hdr{margin-bottom:18px}
.panel-title{display:flex;align-items:center;gap:10px;font-size:21px;font-weight:700}
.panel-title i{color:var(--primary)}
.panel-sub{font-size:13px;color:var(--txt3);margin-top:6px;line-height:1.7}

/* ===== 検索バー ===== */
.search-bar{
  display:flex;align-items:center;gap:10px;
  background:var(--bg1);border:1px solid var(--border);border-radius:999px;
  padding:4px 6px 4px 18px;margin-bottom:14px;
  transition:border-color .15s,box-shadow .15s;
}
.search-bar:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-dim)}
.search-bar i.lead{font-size:19px;color:var(--txt3)}
.search-bar input{
  flex:1;min-width:0;border:0;outline:0;background:none;
  font-family:inherit;font-size:14px;color:var(--txt1);
  height:40px;
}
.search-btn{
  display:inline-flex;align-items:center;gap:7px;
  height:38px;padding:0 20px;border:none;border-radius:999px;
  background:var(--primary);color:#fff;
  font-size:13px;font-weight:500;font-family:inherit;
  cursor:pointer;transition:filter .15s;flex-shrink:0;
}
.search-btn:hover{filter:brightness(1.1)}
.search-btn i{font-size:16px}

/* ===== プロバイダー選択ピル ===== */
.provider-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.provider-tab{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 16px;border-radius:999px;
  background:var(--bg1);border:1px solid var(--border);
  font-size:13px;font-family:inherit;color:var(--txt2);
  cursor:pointer;transition:background .15s;
}
.provider-tab:hover{background:var(--bg2)}
.provider-tab.active{background:var(--primary-dim);color:var(--primary);border-color:transparent;font-weight:600}
.provider-tab i{font-size:16px}
.provider-tab .ext{font-size:13px;opacity:.6}

/* ===== 結果: 画像 / 動画グリッド ===== */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}

.asset-card{display:block;color:inherit;text-decoration:none;cursor:pointer}
.asset-thumb{
  position:relative;overflow:hidden;
  border-radius:12px;aspect-ratio:4/3;
  background:var(--bg3);border:1px solid var(--border);
}
.asset-thumb.wide{aspect-ratio:16/9}
.asset-thumb img,.asset-thumb video{width:100%;height:100%;object-fit:cover;display:block}
.asset-card:hover .asset-thumb{filter:brightness(.95)}
.duration{
  position:absolute;bottom:6px;right:8px;
  background:rgba(0,0,0,.8);color:#fff;
  font-size:12px;font-weight:500;
  padding:2px 6px;border-radius:4px;
}
.play-badge{
  position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:44px;text-shadow:0 2px 12px rgba(0,0,0,.5);
  opacity:.9;pointer-events:none;
}
.asset-meta{display:flex;align-items:center;gap:8px;padding:8px 2px 0}
.asset-meta .info{min-width:0;flex:1}
.asset-meta h3{
  font-size:13px;font-weight:500;line-height:1.4;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.asset-meta p{font-size:12px;color:var(--txt3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.asset-open{
  display:inline-flex;align-items:center;justify-content:center;
  width:30px;height:30px;border-radius:50%;flex-shrink:0;
  color:var(--txt3);font-size:16px;text-decoration:none;
  transition:background .15s,color .15s;
}
.asset-open:hover{background:var(--bg2);color:var(--primary)}

/* ===== 結果: オーディオリスト ===== */
.audio-list{display:flex;flex-direction:column;gap:10px}
.audio-row{
  display:flex;align-items:center;gap:14px;
  background:var(--bg1);border:1px solid var(--border);border-radius:var(--radius);
  padding:12px 14px;
  cursor:pointer;transition:background .15s,border-color .15s;
}
.audio-row:hover{background:var(--bg2)}
.audio-row.playing{border-color:var(--primary)}
.audio-play{
  width:42px;height:42px;border-radius:50%;border:none;flex-shrink:0;
  background:var(--primary-dim);color:var(--primary);
  display:inline-flex;align-items:center;justify-content:center;
  font-size:20px;cursor:pointer;transition:filter .15s;
}
.audio-play:hover{filter:brightness(.96)}
.audio-row.playing .audio-play{background:var(--primary);color:#fff}
.audio-thumb{
  width:42px;height:42px;border-radius:10px;object-fit:cover;flex-shrink:0;
  background:var(--bg3);border:1px solid var(--border);
}
.audio-info{min-width:0;flex:1}
.audio-title{font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.audio-author{font-size:12px;color:var(--txt3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* 一覧の詳細行 (例: 2019.09 | 幻想的 | ピアノ | 2分37秒/2.40 MB) */
.audio-desc{font-size:12px;color:var(--txt2);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.audio-duration{font-size:12px;color:var(--txt3);flex-shrink:0}

/* リンク型プロバイダーのサイト内検索ボタン行 (結果一覧の上) */
.link-search-row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}

/* 結果一覧の後ろに続く案内カード (キー未設定など) は間隔を空ける */
.results > * + .notice-card{margin-top:16px}

/* ===== 案内カード (リンク型プロバイダー / キー未設定) ===== */
.notice-card{
  background:var(--bg1);border:1px solid var(--border);border-radius:var(--radius);
  padding:28px 24px;text-align:center;box-shadow:var(--shadow);
}
.notice-card i.big{font-size:40px;color:var(--primary);display:block;margin-bottom:10px}
.notice-card h2{font-size:16px;font-weight:600;margin-bottom:8px}
.notice-card p{font-size:13px;color:var(--txt2);line-height:1.9}
.notice-card code{
  font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;
  font-size:12px;background:var(--bg2);border:1px solid var(--border);
  border-radius:6px;padding:1px 7px;
}
.notice-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:18px}
.m-button{
  display:inline-flex;align-items:center;gap:7px;
  height:38px;padding:0 20px;border:none;border-radius:999px;
  background:var(--primary);color:#fff;
  font-size:13px;font-weight:500;font-family:inherit;
  text-decoration:none;cursor:pointer;transition:filter .15s;
}
.m-button:hover{filter:brightness(1.1)}
.m-button i{font-size:17px}
.m-button.is-tonal{background:var(--primary-dim);color:var(--primary)}

/* ===== 情報モーダル (素材カードのクリックで表示) ===== */
.modal-overlay{
  position:fixed;inset:0;z-index:200;
  display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.45);padding:20px;
}
.modal-overlay[hidden]{display:none}
.modal{
  background:var(--bg1);border:1px solid var(--border);border-radius:24px;
  box-shadow:var(--shadow);
  width:100%;max-width:520px;max-height:85vh;
  display:flex;flex-direction:column;overflow:hidden;
}
.modal-hdr{display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid var(--border)}
.modal-title{flex:1;display:flex;align-items:center;gap:8px;font-size:16px;font-weight:600;min-width:0}
.modal-title i{color:var(--primary);font-size:19px;flex-shrink:0}
.modal-title span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.modal-close{
  display:flex;padding:6px;border:0;border-radius:50%;
  background:none;color:var(--txt3);font-size:20px;cursor:pointer;
}
.modal-close:hover{background:var(--bg2);color:var(--txt1)}
.modal-body{padding:20px;overflow-y:auto}
.info-meta{font-size:13px;color:var(--txt3)}
/* 詳細原文 (例: 2016.01 | 疾走感 | シネマティック | 1分53秒/1.72 MB \n 使用楽器：…) */
.info-desc{
  font-size:13px;color:var(--txt1);line-height:1.9;white-space:pre-line;
  background:var(--bg0);border:1px solid var(--border);border-radius:12px;
  padding:12px 16px;margin-top:12px;
}
.modal-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}

/* ===== 読み込み中 / エラー表示 ===== */
.placeholder{color:var(--txt3);font-size:13px;padding:8px 2px;display:flex;align-items:center;gap:8px}
.placeholder i{font-size:18px}
.placeholder .spin{animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ===== 再生バー (オーディオ) ===== */
.player{
  position:fixed;left:0;right:0;bottom:0;z-index:80;
  height:var(--player-h);
  display:none;align-items:center;gap:14px;
  padding:0 18px;
  background:var(--bg1);border-top:1px solid var(--border);
  box-shadow:0 -4px 24px rgba(0,0,0,.06);
}
.player.show{display:flex}
.player .audio-play{width:38px;height:38px;font-size:18px;background:var(--primary);color:#fff}
.player-info{min-width:0;width:220px;flex-shrink:1}
.player-seek{flex:1;accent-color:var(--primary);height:4px;cursor:pointer}
.player-time{font-size:12px;color:var(--txt3);flex-shrink:0;font-variant-numeric:tabular-nums}
.player-close{
  display:inline-flex;align-items:center;justify-content:center;
  width:32px;height:32px;border-radius:50%;border:none;background:none;
  color:var(--txt3);font-size:18px;cursor:pointer;
}
.player-close:hover{background:var(--bg2);color:var(--txt1)}
body.player-open .panel{padding-bottom:calc(var(--player-h) + 32px)}

/* ===== 下部固定バー (モバイル) ===== */
.bottom-nav{
  display:none;
  position:fixed;left:0;right:0;bottom:0;z-index:50;
  height:calc(var(--bottom-nav-h) + env(safe-area-inset-bottom));
  padding-bottom:env(safe-area-inset-bottom);
  background:var(--bg1);
  border-top:1px solid var(--border);
  align-items:stretch;
}
.bottom-nav-item{
  flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;
  font-size:10px;font-weight:500;color:var(--txt3);
  text-decoration:none;padding:6px 4px;
  transition:color .15s;
}
.bottom-nav-item i{font-size:23px}
.bottom-nav-item.active,.bottom-nav-item:hover{color:var(--primary)}

/* ===== レスポンシブ: モバイル (≤768px) ===== */
@media (max-width:768px){
  .sidebar{display:none}
  .bottom-nav{display:flex}
  .mobile-brand{display:flex}
  .topbar-title{display:none}
  .topbar{padding:0 14px}

  .panel{padding:20px 14px calc(var(--bottom-nav-h) + env(safe-area-inset-bottom) + 24px)}
  body.player-open .panel{padding-bottom:calc(var(--bottom-nav-h) + var(--player-h) + env(safe-area-inset-bottom) + 24px)}
  .player{bottom:calc(var(--bottom-nav-h) + env(safe-area-inset-bottom))}
  .player-info{width:auto}
  .player-time{display:none}

  .grid{grid-template-columns:repeat(2,1fr);gap:10px}
}

/* ===== レスポンシブ: タブレット (769–1024px) ===== */
@media (min-width:769px) and (max-width:1024px){
  .sidebar{width:190px}
}
</style>
</head>
<body>

<div class="wrapper">

  <!-- サイドバー (デスクトップ) -->
  <nav class="sidebar" aria-label="メインナビゲーション">
    <a class="brand" href="#bgm">
      <span class="brand-logo"><i class="ti ti-sparkles" aria-hidden="true"></i></span>
      <span class="brand-name">Auriga Assets<small>フリー素材検索</small></span>
    </a>

    <a class="nav-item" data-tab="bgm"   href="#bgm"><i class="ti ti-music" aria-hidden="true"></i> BGM</a>
    <a class="nav-item" data-tab="se"    href="#se"><i class="ti ti-wave-sine" aria-hidden="true"></i> SE (効果音)</a>
    <a class="nav-item" data-tab="image" href="#image"><i class="ti ti-photo" aria-hidden="true"></i> 画像</a>
    <a class="nav-item" data-tab="video" href="#video"><i class="ti ti-video" aria-hidden="true"></i> 動画</a>
    <a class="nav-item" href="admin.php"><i class="ti ti-database-plus" aria-hidden="true"></i> 素材の登録</a>

    <div class="sidebar-foot">
      素材の利用条件は各配布元の<br>ライセンスに従ってください。<br>© 2026 Auriga Labs
    </div>
  </nav>

  <!-- メイン -->
  <div class="main">

    <header class="topbar">
      <a class="mobile-brand" href="#bgm">
        <span class="brand-logo"><i class="ti ti-sparkles" aria-hidden="true"></i></span>
        <span class="brand-name">Auriga Assets</span>
      </a>
      <div class="topbar-title" id="topbar-title">BGM</div>
    </header>

    <!-- タブごとのパネル (中身は JS が共通テンプレートで生成する) -->
    <section class="panel" id="panel-bgm"   aria-label="BGM"></section>
    <section class="panel" id="panel-se"    aria-label="SE (効果音)"></section>
    <section class="panel" id="panel-image" aria-label="画像"></section>
    <section class="panel" id="panel-video" aria-label="動画"></section>

  </div><!-- /.main -->
</div><!-- /.wrapper -->

<!-- 素材情報モーダル -->
<div class="modal-overlay" id="info-modal" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="info-modal-title">
    <div class="modal-hdr">
      <div class="modal-title"><i class="ti ti-info-circle" aria-hidden="true"></i> <span id="info-modal-title"></span></div>
      <button class="modal-close" type="button" id="info-modal-close" aria-label="閉じる"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
    <div class="modal-body">
      <p class="info-meta" id="info-modal-meta"></p>
      <p class="info-desc" id="info-modal-desc"></p>
      <div class="modal-actions">
        <button class="m-button" id="info-modal-play" type="button"><i class="ti ti-player-play" aria-hidden="true"></i> <span>再生</span></button>
        <a class="m-button is-tonal" id="info-modal-page" target="_blank" rel="noopener"><i class="ti ti-external-link" aria-hidden="true"></i> 配布元リンクを開く</a>
      </div>
    </div>
  </div>
</div>

<!-- オーディオ再生バー -->
<div class="player" id="player">
  <button class="audio-play" id="player-toggle" type="button" aria-label="再生 / 一時停止"><i class="ti ti-player-pause" aria-hidden="true"></i></button>
  <div class="player-info audio-info">
    <div class="audio-title" id="player-title"></div>
    <div class="audio-author" id="player-author"></div>
  </div>
  <input class="player-seek" id="player-seek" type="range" min="0" max="1000" value="0" aria-label="シークバー">
  <span class="player-time" id="player-time">0:00 / 0:00</span>
  <button class="player-close" id="player-close" type="button" aria-label="閉じる"><i class="ti ti-x" aria-hidden="true"></i></button>
</div>

<!-- 下部固定バー (モバイル) -->
<nav class="bottom-nav" aria-label="ボトムナビゲーション">
  <a class="bottom-nav-item" data-tab="bgm" href="#bgm">
    <i class="ti ti-music" aria-hidden="true"></i><span>BGM</span>
  </a>
  <a class="bottom-nav-item" data-tab="se" href="#se">
    <i class="ti ti-wave-sine" aria-hidden="true"></i><span>SE</span>
  </a>
  <a class="bottom-nav-item" data-tab="image" href="#image">
    <i class="ti ti-photo" aria-hidden="true"></i><span>画像</span>
  </a>
  <a class="bottom-nav-item" data-tab="video" href="#video">
    <i class="ti ti-video" aria-hidden="true"></i><span>動画</span>
  </a>
  <a class="bottom-nav-item" href="admin.php">
    <i class="ti ti-database-plus" aria-hidden="true"></i><span>登録</span>
  </a>
</nav>

<script>
/* ===== プロバイダー定義 (providers.php と共有) =====
   mode: 'api'  → api.php 経由でインライン検索 (id は api.php の provider 名)
         'link' → searchUrl の {q} を検索語に置換して新しいタブで開く
   どのプロバイダーでも、ローカル DB でそのプロバイダーに割り当てた素材を
   一覧の先頭に描画する */
const PROVIDERS = <?= json_encode(require __DIR__ . '/providers.php', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const google = site => q => `https://www.google.com/search?q=site:${site}+${encodeURIComponent(q)}`;
const providerSearchUrl = (p, q) => p.searchUrl.replaceAll('{q}', encodeURIComponent(q));

/* 「すべて」ピル: ローカル DB 全件 + API 対応プロバイダーの横断検索 */
const ALL_PROVIDER = { id: '__all', label: 'すべて', mode: 'api' };

/* プロバイダー id → ラベル (「すべて」表示時の出所表記に使う) */
const PROVIDER_LABELS = {};
for (const list of Object.values(PROVIDERS)) {
  for (const p of list) PROVIDER_LABELS[p.id] = p.label;
}

const TABS = {
  bgm:   { label: 'BGM',       icon: 'music',     sub: 'フリー BGM をプロバイダー横断で検索します。',
           placeholder: '曲の雰囲気やキーワード (例: ピアノ 切ない)' },
  se:    { label: 'SE (効果音)', icon: 'wave-sine', sub: 'フリー効果音を検索します。',
           placeholder: '効果音のキーワード (例: 決定音, クリック)' },
  image: { label: '画像',       icon: 'photo',     sub: 'フリー写真・イラストを検索します。',
           placeholder: '画像のキーワード (例: 猫, 空)' },
  video: { label: '動画',       icon: 'video',     sub: 'フリー動画素材を検索します。',
           placeholder: '動画のキーワード (例: 海, タイムラプス)' },
};

/* タブごとの状態: 選択中プロバイダー / 最後に検索した語 */
const state = {};
for (const tab of Object.keys(TABS)) {
  state[tab] = { provider: ALL_PROVIDER.id, query: '' };
}

/* ===== パネル生成 (全タブ共通テンプレート) ===== */
function buildPanel(tab) {
  const t = TABS[tab];
  const panel = document.getElementById('panel-' + tab);
  panel.innerHTML = `
    <div class="panel-inner">
      <div class="panel-hdr">
        <h1 class="panel-title"><i class="ti ti-${t.icon}" aria-hidden="true"></i> ${t.label}</h1>
        <p class="panel-sub">${t.sub}</p>
      </div>
      <form class="search-bar" data-search="${tab}">
        <i class="ti ti-search lead" aria-hidden="true"></i>
        <input type="search" placeholder="${t.placeholder}" aria-label="${t.label}を検索" autocomplete="off">
        <button class="search-btn" type="submit"><i class="ti ti-search" aria-hidden="true"></i> 検索</button>
      </form>
      <div class="provider-tabs" role="tablist" aria-label="プロバイダー選択">
        ${[ALL_PROVIDER, ...PROVIDERS[tab]].map((p, i) => `
          <button class="provider-tab${i === 0 ? ' active' : ''}" type="button" role="tab"
                  aria-selected="${i === 0}" data-provider="${p.id}">
            ${p.label}${p.mode === 'link' ? ' <i class="ti ti-external-link ext" aria-hidden="true"></i>' : ''}
          </button>`).join('')}
      </div>
      <div class="results" id="results-${tab}"></div>
    </div>`;

  /* 検索フォーム */
  panel.querySelector('form').addEventListener('submit', e => {
    e.preventDefault();
    state[tab].query = panel.querySelector('input').value.trim();
    runSearch(tab);
  });

  /* プロバイダーピル */
  const pills = panel.querySelectorAll('.provider-tab');
  pills.forEach(btn => btn.addEventListener('click', () => {
    pills.forEach(b => {
      b.classList.toggle('active', b === btn);
      b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
    });
    state[tab].provider = btn.dataset.provider;
    runSearch(tab);
  }));

  /* 初期表示: 先頭プロバイダーに割り当てた登録済み素材を読み込む */
  runSearch(tab);
}

function provider(tab) {
  if (state[tab].provider === ALL_PROVIDER.id) return ALL_PROVIDER;
  return PROVIDERS[tab].find(p => p.id === state[tab].provider);
}

/* ===== 検索実行 =====
   どのプロバイダーを選んでいても、まずローカル DB からそのプロバイダーに
   割り当てた素材を取得して一覧の先頭に描画する。API 型はさらにオンライン
   検索結果を続けて表示し、リンク型は登録済み素材 + サイト内検索ボタンを出す */
async function runSearch(tab) {
  const p = provider(tab);
  const q = state[tab].query;
  const box = document.getElementById('results-' + tab);
  const stale = () => state[tab].provider !== p.id || state[tab].query !== q;

  box.replaceChildren(placeholderMessage('loader-2 spin', '検索中…'));

  let localItems = [];
  try {
    const target = p.id === ALL_PROVIDER.id ? '' : p.id;
    const res = await fetch(`api.php?provider=local&kind=${tab}&target=${encodeURIComponent(target)}&q=${encodeURIComponent(q)}`);
    localItems = (await res.json()).items ?? [];
    for (const it of localItems) it._src = PROVIDER_LABELS[it.provider] ?? p.label;
  } catch (e) {
    console.error('ローカルDBの取得に失敗:', e);
  }
  if (stale()) return;

  /* すべて: ローカル DB 全件 + API 対応プロバイダーを横断検索して結合する */
  if (p.id === ALL_PROVIDER.id) {
    if (q === '') {
      if (localItems.length > 0) box.replaceChildren(renderItems(localItems, p));
      else renderIdle(tab);
      return;
    }
    const apiProviders = PROVIDERS[tab].filter(x => x.mode === 'api');
    const results = await Promise.all(apiProviders.map(async x => {
      /* キー未設定・失敗したプロバイダーは黙ってスキップする */
      try {
        const res = await fetch(`api.php?provider=${encodeURIComponent(x.id)}&q=${encodeURIComponent(q)}`);
        const d = await res.json();
        return (d.items ?? []).map(it => ({ ...it, _src: x.label }));
      } catch { return []; }
    }));
    if (stale()) return;
    const items = [...localItems, ...results.flat()];
    if (items.length === 0) {
      box.replaceChildren(placeholderMessage('zoom-question', `「${q}」に一致する素材が見つかりませんでした`));
      return;
    }
    box.replaceChildren(renderItems(items, p));
    return;
  }

  /* リンク型: 登録済み素材を描画し、サイト内検索は外部リンクボタンで開く */
  if (p.mode === 'link') {
    const frag = document.createDocumentFragment();
    if (q !== '') {
      const row = document.createElement('div');
      row.className = 'link-search-row';
      row.append(...noticeActions([{ label: `${p.label} のサイトで「${q}」を検索`, icon: 'external-link', tonal: true,
                                     href: providerSearchUrl(p, q) }]));
      frag.append(row);
    }
    if (localItems.length > 0) {
      frag.append(renderItems(localItems, p));
    } else if (q === '') {
      frag.append(noticeCard({
        icon: 'database-off',
        title: `${p.label} に登録済みの素材はありません`,
        html: `登録画面で ${p.label} に割り当てて素材を追加すると、ここに一覧表示されます。<br>` +
              `${p.label} は API を公開していないため、キーワード検索はサイト内検索を開きます。`,
        actions: [{ label: '素材を登録する', icon: 'database-plus', href: 'admin.php', self: true }],
      }));
    } else {
      frag.append(placeholderMessage('zoom-question', `登録済み素材に「${q}」は見つかりませんでした`));
    }
    box.replaceChildren(frag);
    return;
  }

  /* API 型: キーワード空なら登録済み素材のみ表示する */
  if (q === '') {
    if (localItems.length > 0) box.replaceChildren(renderItems(localItems, p));
    else renderIdle(tab);
    return;
  }

  let data;
  try {
    const res = await fetch(`api.php?provider=${encodeURIComponent(p.id)}&q=${encodeURIComponent(q)}`);
    data = await res.json();
  } catch (e) {
    console.error('検索に失敗:', e);
    data = { error: 'http' };
  }
  if (stale()) return;

  if (data.error === 'no_key') {
    const notice = noticeCard({
      icon: 'key',
      title: `${p.label} の API キーが未設定です`,
      html: `<code>config.php</code> の <code>${p.keyName}</code> にキーを設定すると、ここに検索結果が表示されます。`,
      actions: [
        { label: 'API キーを取得', icon: 'key', href: p.keyUrl },
        { label: `${p.label} のサイトで検索`, icon: 'external-link', tonal: true, href: google(p.site)(q) },
      ],
    });
    if (localItems.length > 0) {
      const frag = document.createDocumentFragment();
      frag.append(renderItems(localItems, p), notice);
      box.replaceChildren(frag);
    } else {
      box.replaceChildren(notice);
    }
    return;
  }

  const items = [...localItems, ...(data.error ? [] : (data.items ?? []))];
  if (data.error && items.length === 0) {
    box.replaceChildren(placeholderMessage('alert-circle', `検索に失敗しました (${data.error})`));
    return;
  }
  if (items.length === 0) {
    box.replaceChildren(placeholderMessage('zoom-question', `「${q}」に一致する素材が見つかりませんでした`));
    return;
  }
  box.replaceChildren(renderItems(items, p));
}

/* 種別に応じたレンダラーへ振り分けて要素を返す */
function renderItems(items, p) {
  const kind = items[0].type;
  if (kind === 'audio') return renderAudioList(items, p);
  if (kind === 'video') return renderVideoGrid(items, p);
  return renderImageGrid(items, p);
}

/* ===== 描画: 待機状態 ===== */
function renderIdle(tab) {
  const box = document.getElementById('results-' + tab);
  box.replaceChildren(placeholderMessage('search', 'キーワードを入力して検索してください'));
}

function placeholderMessage(icon, text) {
  const p = document.createElement('p');
  p.className = 'placeholder';
  p.innerHTML = `<i class="ti ti-${icon}" aria-hidden="true"></i> `;
  p.append(text);
  return p;
}

function noticeActions(actions) {
  return actions.map(a => {
    const btn = document.createElement('a');
    btn.className = 'm-button' + (a.tonal ? ' is-tonal' : '');
    btn.href = a.href;
    if (!a.self) { btn.target = '_blank'; btn.rel = 'noopener'; }
    btn.innerHTML = `<i class="ti ti-${a.icon}" aria-hidden="true"></i> `;
    btn.append(a.label);
    return btn;
  });
}

function noticeCard({ icon, title, html, actions = [] }) {
  const div = document.createElement('div');
  div.className = 'notice-card';
  div.innerHTML = `
    <i class="ti ti-${icon} big" aria-hidden="true"></i>
    <h2></h2>
    <p>${html}</p>
    <div class="notice-actions"></div>`;
  div.querySelector('h2').textContent = title;
  div.querySelector('.notice-actions').append(...noticeActions(actions));
  return div;
}

/* 結果メタ行の出所表示 (「すべて」表示時は素材ごとのプロバイダー名) */
function sourceLabel(it, p) {
  return it._src ?? p.label;
}

/* ===== 描画: 画像グリッド ===== */
function renderImageGrid(items, p) {
  const grid = document.createElement('div');
  grid.className = 'grid';
  for (const it of items) {
    const a = document.createElement('a');
    a.className = 'asset-card';
    a.href = it.pageUrl || it.full;
    a.target = '_blank';
    a.rel = 'noopener';
    a.innerHTML = `
      <div class="asset-thumb"><img loading="lazy" alt=""></div>
      <div class="asset-meta">
        <div class="info"><h3></h3><p></p></div>
        <span class="asset-open"><i class="ti ti-external-link" aria-hidden="true"></i></span>
      </div>`;
    a.querySelector('img').src = it.thumb;
    a.querySelector('h3').textContent = it.title || p.label;
    a.querySelector('p').textContent = it.author ? `by ${it.author} · ${sourceLabel(it, p)}` : sourceLabel(it, p);
    grid.append(a);
  }
  return grid;
}

/* ===== 描画: 動画グリッド (クリックでインライン再生) ===== */
function renderVideoGrid(items, p) {
  const grid = document.createElement('div');
  grid.className = 'grid';
  for (const it of items) {
    const card = document.createElement('div');
    card.className = 'asset-card';
    card.innerHTML = `
      <div class="asset-thumb wide">
        <img loading="lazy" alt="">
        <span class="play-badge"><i class="ti ti-player-play" aria-hidden="true"></i></span>
        <span class="duration"></span>
      </div>
      <div class="asset-meta">
        <div class="info"><h3></h3><p></p></div>
        <a class="asset-open" target="_blank" rel="noopener" title="配布ページを開く"><i class="ti ti-external-link" aria-hidden="true"></i></a>
      </div>`;
    card.querySelector('img').src = it.thumb;
    card.querySelector('.duration').textContent = formatDuration(it.duration);
    card.querySelector('h3').textContent = it.title || p.label;
    card.querySelector('p').textContent = it.author ? `by ${it.author} · ${sourceLabel(it, p)}` : sourceLabel(it, p);
    const open = card.querySelector('.asset-open');
    if (it.pageUrl) open.href = it.pageUrl; else open.remove();

    /* サムネイルクリックでインライン再生に差し替える */
    const thumb = card.querySelector('.asset-thumb');
    thumb.addEventListener('click', () => {
      if (!it.preview) { window.open(it.pageUrl, '_blank', 'noopener'); return; }
      const video = document.createElement('video');
      video.src = it.preview;
      video.controls = true;
      video.autoplay = true;
      video.playsInline = true;
      thumb.replaceChildren(video);
    }, { once: true });
    grid.append(card);
  }
  return grid;
}

/* ===== 描画: オーディオリスト ===== */
function renderAudioList(items, p) {
  const list = document.createElement('div');
  list.className = 'audio-list';
  for (const it of items) {
    const row = document.createElement('div');
    row.className = 'audio-row';
    row.innerHTML = `
      <button class="audio-play" type="button" aria-label="再生 / 一時停止"><i class="ti ti-player-play" aria-hidden="true"></i></button>
      ${it.thumb ? '<img class="audio-thumb" loading="lazy" alt="">' : ''}
      <div class="audio-info">
        <div class="audio-title"></div>
        <div class="audio-author"></div>
      </div>
      <span class="audio-duration"></span>
      <a class="asset-open" target="_blank" rel="noopener" title="配布ページを開く"><i class="ti ti-external-link" aria-hidden="true"></i></a>`;
    if (it.thumb) row.querySelector('.audio-thumb').src = it.thumb;
    row.querySelector('.audio-title').textContent = it.title;
    row.querySelector('.audio-author').textContent = it.author ? `${it.author} · ${sourceLabel(it, p)}` : sourceLabel(it, p);
    /* 詳細の 1 行目 (例: 2019.09 | 幻想的 | ピアノ | 2分37秒/2.40 MB) を一覧にも表示する */
    const descLine = (it.description ?? '').split('\n')[0];
    if (descLine) {
      const desc = document.createElement('div');
      desc.className = 'audio-desc';
      desc.textContent = descLine;
      row.querySelector('.audio-info').append(desc);
    }
    row.querySelector('.audio-duration').textContent = formatDuration(it.duration);
    const open = row.querySelector('.asset-open');
    if (it.pageUrl) open.href = it.pageUrl; else open.remove();
    row.querySelector('.audio-play').addEventListener('click', () => playAudio(it, row));
    /* 再生ボタン・リンク以外のカード全体クリックで情報モーダルを開く */
    row.addEventListener('click', e => {
      if (e.target.closest('button, a')) return;
      openInfoModal(it, p, row);
    });
    list.append(row);
  }
  return list;
}

/* ===== 情報モーダル (カード全体のクリックで表示) ===== */
const infoModal = document.getElementById('info-modal');
let modalItem = null;   /* モーダルが表示している素材 */
let modalRow  = null;   /* その素材の一覧上の行 (再生状態の照合に使う) */

function openInfoModal(it, p, row = null) {
  modalItem = it;
  modalRow  = row;
  document.getElementById('info-modal-title').textContent = it.title || p.label;

  const meta = [it.author, sourceLabel(it, p), it.duration ? formatDuration(it.duration) : '']
    .filter(Boolean).join(' · ');
  document.getElementById('info-modal-meta').textContent = meta;

  /* 詳細原文 (例: 2016.01 | 疾走感 | シネマティック | 1分53秒/1.72 MB + 使用楽器行)。
     description が無い素材 (API 検索結果など) では枠ごと非表示にする */
  const desc = document.getElementById('info-modal-desc');
  desc.textContent = it.description ?? '';
  desc.hidden = !it.description;

  const page = document.getElementById('info-modal-page');
  page.href = it.pageUrl || '';
  page.hidden = !it.pageUrl;

  updateModalPlayButton();
  infoModal.hidden = false;
}

function closeInfoModal() { infoModal.hidden = true; }

/* モーダルの再生ボタンを現在の再生状態に合わせる (再生中なら一時停止表示) */
function updateModalPlayButton() {
  const btn = document.getElementById('info-modal-play');
  if (!modalItem || !modalItem.preview) { btn.hidden = true; return; }
  btn.hidden = false;
  const playing = modalRow !== null && modalRow === playingRow && !audio.paused;
  btn.querySelector('i').className = playing ? 'ti ti-player-pause' : 'ti ti-player-play';
  btn.querySelector('span').textContent = playing ? '一時停止' : '再生';
}

document.getElementById('info-modal-play').addEventListener('click', () => {
  if (modalItem) playAudio(modalItem, modalRow);
});

document.getElementById('info-modal-close').addEventListener('click', closeInfoModal);
infoModal.addEventListener('click', e => { if (e.target === infoModal) closeInfoModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && !infoModal.hidden) closeInfoModal(); });

/* ===== オーディオ再生 (画面下の再生バーで共有 1 トラック再生) ===== */
const audio = new Audio();
let playingRow = null;

const player = {
  bar:    document.getElementById('player'),
  toggle: document.getElementById('player-toggle'),
  title:  document.getElementById('player-title'),
  author: document.getElementById('player-author'),
  seek:   document.getElementById('player-seek'),
  time:   document.getElementById('player-time'),
  close:  document.getElementById('player-close'),
};

function playAudio(it, row) {
  if (playingRow === row) { togglePause(); return; }

  setRowPlaying(row);
  audio.src = it.preview;
  audio.play().catch(e => console.error('再生に失敗:', e));

  player.title.textContent = it.title;
  player.author.textContent = it.author;
  player.bar.classList.add('show');
  document.body.classList.add('player-open');
}

function setRowPlaying(row) {
  if (playingRow) {
    playingRow.classList.remove('playing');
    playingRow.querySelector('.audio-play i').className = 'ti ti-player-play';
  }
  playingRow = row;
  if (row) {
    row.classList.add('playing');
    row.querySelector('.audio-play i').className = 'ti ti-player-pause';
  }
}

function togglePause() {
  if (audio.paused) audio.play(); else audio.pause();
}

audio.addEventListener('play',  () => syncPlayIcons(true));
audio.addEventListener('pause', () => syncPlayIcons(false));
audio.addEventListener('ended', () => syncPlayIcons(false));

function syncPlayIcons(playing) {
  player.toggle.querySelector('i').className = playing ? 'ti ti-player-pause' : 'ti ti-player-play';
  /* 検索し直しで行が消えていることがあるので isConnected を確認する */
  if (playingRow && playingRow.isConnected) {
    playingRow.classList.toggle('playing', playing);
    playingRow.querySelector('.audio-play i').className = playing ? 'ti ti-player-pause' : 'ti ti-player-play';
  }
  /* モーダルを開いたまま再生状態が変わったときも追従させる */
  if (!infoModal.hidden) updateModalPlayButton();
}

audio.addEventListener('timeupdate', () => {
  if (audio.duration > 0 && !seekDragging) {
    player.seek.value = Math.round(audio.currentTime / audio.duration * 1000);
  }
  player.time.textContent = `${formatDuration(audio.currentTime)} / ${formatDuration(audio.duration || 0)}`;
});

let seekDragging = false;
player.seek.addEventListener('input', () => { seekDragging = true; });
player.seek.addEventListener('change', () => {
  if (audio.duration > 0) audio.currentTime = player.seek.value / 1000 * audio.duration;
  seekDragging = false;
});

player.toggle.addEventListener('click', togglePause);
player.close.addEventListener('click', () => {
  audio.pause();
  audio.removeAttribute('src');
  setRowPlaying(null);
  player.bar.classList.remove('show');
  document.body.classList.remove('player-open');
});

/* ===== 表示フォーマット ===== */
function formatDuration(sec) {
  sec = Math.round(sec);
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = sec % 60;
  return h > 0
    ? `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
    : `${m}:${String(s).padStart(2, '0')}`;
}

/* ===== タブ切り替え (ハッシュルーティング) ===== */
function currentTab() {
  const h = location.hash.replace('#', '');
  return TABS[h] ? h : 'bgm';
}

function activateTab() {
  const tab = currentTab();
  document.querySelectorAll('[data-tab]').forEach(el =>
    el.classList.toggle('active', el.dataset.tab === tab));
  document.querySelectorAll('.panel').forEach(p =>
    p.classList.toggle('active', p.id === 'panel-' + tab));
  document.getElementById('topbar-title').textContent = TABS[tab].label;
  document.title = `${TABS[tab].label} | Auriga Assets`;
  /* 表示したタブの検索ボックスにフォーカス (デスクトップのみ) */
  if (matchMedia('(min-width:769px)').matches) {
    document.querySelector(`#panel-${tab} input`)?.focus();
  }
}

window.addEventListener('hashchange', activateTab);

for (const tab of Object.keys(TABS)) buildPanel(tab);
activateTab();
</script>
</body>
</html>
