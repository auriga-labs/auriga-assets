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
  padding:10px 14px;
}
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
.audio-duration{font-size:12px;color:var(--txt3);flex-shrink:0}

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

<!-- オーディオ再生バー -->
<div class="player" id="player">
  <button class="audio-play" id="player-toggle" type="button" aria-label="再生 / 一時停止"><i class="ti ti-player-pause-filled" aria-hidden="true"></i></button>
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
</nav>

<script>
/* ===== プロバイダー定義 =====
   mode: 'api'  → api.php 経由でインライン検索 (id は api.php の provider 名)
         'link' → 各サイトの検索ページを新しいタブで開く (url(q) がテンプレート)
   keyName: 'api' のとき config.php のどのキーを使うか (未設定案内に使う) */
const google = site => q => `https://www.google.com/search?q=site:${site}+${encodeURIComponent(q)}`;

const PROVIDERS = {
  bgm: [
    { id: 'jamendo',  label: 'Jamendo',          icon: 'brand-vimeo',  mode: 'api',  keyName: 'jamendo',
      keyUrl: 'https://devportal.jamendo.com/', site: 'jamendo.com' },
    { id: 'dova',     label: 'DOVA-SYNDROME',    icon: 'external-link', mode: 'link', url: google('dova-s.jp') },
    { id: 'maou-bgm', label: '魔王魂',            icon: 'external-link', mode: 'link', url: q => `https://maou.audio/?s=${encodeURIComponent(q)}` },
    { id: 'amacha',   label: '甘茶の音楽工房',     icon: 'external-link', mode: 'link', url: google('amachamusic.chagasi.com') },
    { id: 'musmus',   label: 'MusMus',           icon: 'external-link', mode: 'link', url: google('musmus.main.jp') },
  ],
  se: [
    { id: 'freesound', label: 'Freesound',       icon: 'wave-square',  mode: 'api',  keyName: 'freesound',
      keyUrl: 'https://freesound.org/apiv2/apply/', site: 'freesound.org' },
    { id: 'selab',     label: '効果音ラボ',        icon: 'external-link', mode: 'link', url: google('soundeffect-lab.info') },
    { id: 'otologic',  label: 'OtoLogic',        icon: 'external-link', mode: 'link', url: q => `https://otologic.jp/?s=${encodeURIComponent(q)}` },
    { id: 'maou-se',   label: '魔王魂',           icon: 'external-link', mode: 'link', url: q => `https://maou.audio/?s=${encodeURIComponent(q)}` },
  ],
  image: [
    { id: 'pixabay-image', label: 'Pixabay',     icon: 'photo-search', mode: 'api',  keyName: 'pixabay',
      keyUrl: 'https://pixabay.com/api/docs/', site: 'pixabay.com' },
    { id: 'pexels-image',  label: 'Pexels',      icon: 'photo-search', mode: 'api',  keyName: 'pexels',
      keyUrl: 'https://www.pexels.com/api/', site: 'pexels.com' },
    { id: 'unsplash',      label: 'Unsplash',    icon: 'brand-unsplash', mode: 'api', keyName: 'unsplash',
      keyUrl: 'https://unsplash.com/developers', site: 'unsplash.com' },
    { id: 'irasutoya',     label: 'いらすとや',    icon: 'external-link', mode: 'link', url: q => `https://www.irasutoya.com/search?q=${encodeURIComponent(q)}` },
    { id: 'photoac',       label: '写真AC',       icon: 'external-link', mode: 'link', url: q => `https://www.photo-ac.com/main/search?q=${encodeURIComponent(q)}` },
  ],
  video: [
    { id: 'pixabay-video', label: 'Pixabay',     icon: 'video',        mode: 'api',  keyName: 'pixabay',
      keyUrl: 'https://pixabay.com/api/docs/', site: 'pixabay.com' },
    { id: 'pexels-video',  label: 'Pexels',      icon: 'video',        mode: 'api',  keyName: 'pexels',
      keyUrl: 'https://www.pexels.com/api/', site: 'pexels.com' },
    { id: 'videoac',       label: '動画AC',       icon: 'external-link', mode: 'link', url: google('video-ac.com') },
    { id: 'coverr',        label: 'Coverr',      icon: 'external-link', mode: 'link', url: q => `https://coverr.co/search?q=${encodeURIComponent(q)}` },
  ],
};

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
  state[tab] = { provider: PROVIDERS[tab][0].id, query: '' };
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
        ${PROVIDERS[tab].map((p, i) => `
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
    runSearch(tab, true);
  });

  /* プロバイダーピル */
  const pills = panel.querySelectorAll('.provider-tab');
  pills.forEach(btn => btn.addEventListener('click', () => {
    pills.forEach(b => {
      b.classList.toggle('active', b === btn);
      b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
    });
    state[tab].provider = btn.dataset.provider;
    runSearch(tab, false);
  }));

  renderIdle(tab);
}

function provider(tab) {
  return PROVIDERS[tab].find(p => p.id === state[tab].provider);
}

/* ===== 検索実行 ===== */
async function runSearch(tab, submitted) {
  const p = provider(tab);
  const q = state[tab].query;
  const box = document.getElementById('results-' + tab);

  if (q === '') { renderIdle(tab); return; }

  /* リンク型: 検索ボタンで新しいタブに開く。ピル切り替え時は案内だけ出す */
  if (p.mode === 'link') {
    if (submitted) window.open(p.url(q), '_blank', 'noopener');
    box.replaceChildren(noticeCard({
      icon: 'external-link',
      title: `${p.label} はサイト内検索を開きます`,
      html: `${p.label} は API を公開していないため、検索結果は ${p.label} のページで表示されます。`,
      actions: [{ label: `${p.label} で「${q}」を検索`, icon: 'external-link', href: p.url(q) }],
    }));
    return;
  }

  /* API 型 */
  box.replaceChildren(placeholderMessage('loader-2 spin', '検索中…'));
  let data;
  try {
    const res = await fetch(`api.php?provider=${encodeURIComponent(p.id)}&q=${encodeURIComponent(q)}`);
    data = await res.json();
  } catch (e) {
    console.error('検索に失敗:', e);
    box.replaceChildren(placeholderMessage('alert-circle', '検索に失敗しました'));
    return;
  }

  /* 状態が変わっていたら描画しない (連打対策) */
  if (state[tab].provider !== p.id || state[tab].query !== q) return;

  if (data.error === 'no_key') {
    box.replaceChildren(noticeCard({
      icon: 'key',
      title: `${p.label} の API キーが未設定です`,
      html: `<code>config.php</code> の <code>${p.keyName}</code> にキーを設定すると、ここに検索結果が表示されます。`,
      actions: [
        { label: 'API キーを取得', icon: 'key', href: p.keyUrl },
        { label: `${p.label} のサイトで検索`, icon: 'external-link', tonal: true,
          href: google(p.site)(q) },
      ],
    }));
    return;
  }
  if (data.error) {
    box.replaceChildren(placeholderMessage('alert-circle', `検索に失敗しました (${data.error})`));
    return;
  }
  if (!data.items || data.items.length === 0) {
    box.replaceChildren(placeholderMessage('zoom-question', `「${q}」に一致する素材が見つかりませんでした`));
    return;
  }

  const kind = data.items[0].type;
  if (kind === 'audio')      renderAudioList(box, data.items, p);
  else if (kind === 'video') renderVideoGrid(box, data.items, p);
  else                       renderImageGrid(box, data.items, p);
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

function noticeCard({ icon, title, html, actions = [] }) {
  const div = document.createElement('div');
  div.className = 'notice-card';
  div.innerHTML = `
    <i class="ti ti-${icon} big" aria-hidden="true"></i>
    <h2></h2>
    <p>${html}</p>
    <div class="notice-actions"></div>`;
  div.querySelector('h2').textContent = title;
  const wrap = div.querySelector('.notice-actions');
  for (const a of actions) {
    const btn = document.createElement('a');
    btn.className = 'm-button' + (a.tonal ? ' is-tonal' : '');
    btn.href = a.href;
    btn.target = '_blank';
    btn.rel = 'noopener';
    btn.innerHTML = `<i class="ti ti-${a.icon}" aria-hidden="true"></i> `;
    btn.append(a.label);
    wrap.append(btn);
  }
  return div;
}

/* ===== 描画: 画像グリッド ===== */
function renderImageGrid(box, items, p) {
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
    a.querySelector('p').textContent = it.author ? `by ${it.author} · ${p.label}` : p.label;
    grid.append(a);
  }
  box.replaceChildren(grid);
}

/* ===== 描画: 動画グリッド (クリックでインライン再生) ===== */
function renderVideoGrid(box, items, p) {
  const grid = document.createElement('div');
  grid.className = 'grid';
  for (const it of items) {
    const card = document.createElement('div');
    card.className = 'asset-card';
    card.innerHTML = `
      <div class="asset-thumb wide">
        <img loading="lazy" alt="">
        <span class="play-badge"><i class="ti ti-player-play-filled" aria-hidden="true"></i></span>
        <span class="duration"></span>
      </div>
      <div class="asset-meta">
        <div class="info"><h3></h3><p></p></div>
        <a class="asset-open" target="_blank" rel="noopener" title="配布ページを開く"><i class="ti ti-external-link" aria-hidden="true"></i></a>
      </div>`;
    card.querySelector('img').src = it.thumb;
    card.querySelector('.duration').textContent = formatDuration(it.duration);
    card.querySelector('h3').textContent = it.title || p.label;
    card.querySelector('p').textContent = it.author ? `by ${it.author} · ${p.label}` : p.label;
    card.querySelector('.asset-open').href = it.pageUrl;

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
  box.replaceChildren(grid);
}

/* ===== 描画: オーディオリスト ===== */
function renderAudioList(box, items, p) {
  const list = document.createElement('div');
  list.className = 'audio-list';
  for (const it of items) {
    const row = document.createElement('div');
    row.className = 'audio-row';
    row.innerHTML = `
      <button class="audio-play" type="button" aria-label="再生"><i class="ti ti-player-play-filled" aria-hidden="true"></i></button>
      ${it.thumb ? '<img class="audio-thumb" loading="lazy" alt="">' : ''}
      <div class="audio-info">
        <div class="audio-title"></div>
        <div class="audio-author"></div>
      </div>
      <span class="audio-duration"></span>
      <a class="asset-open" target="_blank" rel="noopener" title="配布ページを開く"><i class="ti ti-external-link" aria-hidden="true"></i></a>`;
    if (it.thumb) row.querySelector('.audio-thumb').src = it.thumb;
    row.querySelector('.audio-title').textContent = it.title;
    row.querySelector('.audio-author').textContent = it.author ? `${it.author} · ${p.label}` : p.label;
    row.querySelector('.audio-duration').textContent = formatDuration(it.duration);
    row.querySelector('.asset-open').href = it.pageUrl;
    row.querySelector('.audio-play').addEventListener('click', () => playAudio(it, row));
    list.append(row);
  }
  box.replaceChildren(list);
}

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
    playingRow.querySelector('.audio-play i').className = 'ti ti-player-play-filled';
  }
  playingRow = row;
  if (row) {
    row.classList.add('playing');
    row.querySelector('.audio-play i').className = 'ti ti-player-pause-filled';
  }
}

function togglePause() {
  if (audio.paused) audio.play(); else audio.pause();
}

audio.addEventListener('play',  () => syncPlayIcons(true));
audio.addEventListener('pause', () => syncPlayIcons(false));
audio.addEventListener('ended', () => syncPlayIcons(false));

function syncPlayIcons(playing) {
  player.toggle.querySelector('i').className = playing ? 'ti ti-player-pause-filled' : 'ti ti-player-play-filled';
  /* 検索し直しで行が消えていることがあるので isConnected を確認する */
  if (playingRow && playingRow.isConnected) {
    playingRow.classList.toggle('playing', playing);
    playingRow.querySelector('.audio-play i').className = playing ? 'ti ti-player-pause-filled' : 'ti ti-player-play-filled';
  }
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
