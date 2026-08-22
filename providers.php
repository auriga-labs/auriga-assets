<?php
/* Auriga Assets — プロバイダー定義 (index.php の JS と admin.php のフォームで共有)
   ローカル DB に登録する素材は、このいずれかのプロバイダーに割り当てて保存し、
   検索画面では割り当て先プロバイダーの一覧の先頭に描画される。

   mode 'api'  : api.php の provider 名 = id。keyName は config.php のキー名、
                 site はキー未設定時のフォールバック検索に使うドメイン
   mode 'link' : searchUrl の {q} を検索語 (URL エンコード済み) に置換して
                 新しいタブで開く。API 非公開サイトは Google の site: 検索を使う */
return [
    'bgm' => [
        ['id' => 'jamendo',  'label' => 'Jamendo',       'mode' => 'api',
         'keyName' => 'jamendo', 'keyUrl' => 'https://devportal.jamendo.com/', 'site' => 'jamendo.com'],
        ['id' => 'dova',     'label' => 'DOVA-SYNDROME', 'mode' => 'link',
         'searchUrl' => 'https://www.google.com/search?q=site:dova-s.jp+{q}'],
        ['id' => 'maou-bgm', 'label' => '魔王魂',         'mode' => 'link',
         'searchUrl' => 'https://maou.audio/?s={q}'],
        ['id' => 'amacha',   'label' => '甘茶の音楽工房',  'mode' => 'link',
         'searchUrl' => 'https://www.google.com/search?q=site:amachamusic.chagasi.com+{q}'],
        ['id' => 'musmus',   'label' => 'MusMus',        'mode' => 'link',
         'searchUrl' => 'https://www.google.com/search?q=site:musmus.main.jp+{q}'],
    ],
    'se' => [
        ['id' => 'freesound', 'label' => 'Freesound', 'mode' => 'api',
         'keyName' => 'freesound', 'keyUrl' => 'https://freesound.org/apiv2/apply/', 'site' => 'freesound.org'],
        ['id' => 'selab',     'label' => '効果音ラボ',  'mode' => 'link',
         'searchUrl' => 'https://www.google.com/search?q=site:soundeffect-lab.info+{q}'],
        ['id' => 'otologic',  'label' => 'OtoLogic',  'mode' => 'link',
         'searchUrl' => 'https://otologic.jp/?s={q}'],
        ['id' => 'maou-se',   'label' => '魔王魂',     'mode' => 'link',
         'searchUrl' => 'https://maou.audio/?s={q}'],
    ],
    'image' => [
        ['id' => 'pixabay-image', 'label' => 'Pixabay',  'mode' => 'api',
         'keyName' => 'pixabay', 'keyUrl' => 'https://pixabay.com/api/docs/', 'site' => 'pixabay.com'],
        ['id' => 'pexels-image',  'label' => 'Pexels',   'mode' => 'api',
         'keyName' => 'pexels', 'keyUrl' => 'https://www.pexels.com/api/', 'site' => 'pexels.com'],
        ['id' => 'unsplash',      'label' => 'Unsplash', 'mode' => 'api',
         'keyName' => 'unsplash', 'keyUrl' => 'https://unsplash.com/developers', 'site' => 'unsplash.com'],
        ['id' => 'irasutoya',     'label' => 'いらすとや', 'mode' => 'link',
         'searchUrl' => 'https://www.irasutoya.com/search?q={q}'],
        ['id' => 'photoac',       'label' => '写真AC',    'mode' => 'link',
         'searchUrl' => 'https://www.photo-ac.com/main/search?q={q}'],
    ],
    'video' => [
        ['id' => 'pixabay-video', 'label' => 'Pixabay', 'mode' => 'api',
         'keyName' => 'pixabay', 'keyUrl' => 'https://pixabay.com/api/docs/', 'site' => 'pixabay.com'],
        ['id' => 'pexels-video',  'label' => 'Pexels',  'mode' => 'api',
         'keyName' => 'pexels', 'keyUrl' => 'https://www.pexels.com/api/', 'site' => 'pexels.com'],
        ['id' => 'videoac',       'label' => '動画AC',   'mode' => 'link',
         'searchUrl' => 'https://www.google.com/search?q=site:video-ac.com+{q}'],
        ['id' => 'coverr',        'label' => 'Coverr',  'mode' => 'link',
         'searchUrl' => 'https://coverr.co/search?q={q}'],
    ],
];
