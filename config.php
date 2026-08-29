<?php
/* Auriga Assets — API キー設定
   キーを取得したら '' の中に貼り付ける。空のままのプロバイダーは
   UI 側で「キー未設定」と案内され、外部検索へのリンクにフォールバックする。 */
return [
    /* https://pixabay.com/api/docs/  (無料登録でキー発行、画像+動画共通) */
    'pixabay'   => '',

    /* https://www.pexels.com/api/    (無料登録、画像+動画共通) */
    'pexels'    => '',

    /* https://unsplash.com/developers (Access Key を使う) */
    'unsplash'  => '',

    /* https://devportal.jamendo.com/  (client_id を使う、BGM) */
    'jamendo'   => '',

    /* https://freesound.org/apiv2/apply/ (API token、効果音) */
    'freesound' => '',

    /* Subsonic 互換 API (rest/) のログイン情報。
       DSub / Symfonium などのクライアントからこのユーザー名とパスワードで接続する。
       パスワードが空のままだと Subsonic API は無効になる。 */
    'subsonic_user' => 'auriga',
    'subsonic_pass' => 'p=Toki180918',
];
