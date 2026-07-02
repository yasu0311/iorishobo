<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 商品ファイル 許可拡張子
    |--------------------------------------------------------------------------
    | 教材用商品ファイルのアップロードで許可する拡張子のリストです。
    | 許可リスト方式のため、ここにない拡張子はサーバーで拒否されます。
    */
    'allowed_extensions' => [
        // アーカイブ
        'zip', 'rar', '7z', 'tar', 'gz',
        // 文書
        'pdf', 'doc', 'docx', 'docm', 'xls', 'xlsx', 'xlsm', 'ppt', 'pptx', 'pptm', 'txt',
        // 一太郎・花子
        'jtd', 'jtt', 'jhd',
        // 画像
        'jpg', 'jpeg', 'jfif', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'tif', 'ico', 'heic', 'avif',
    ],

    /*
    |--------------------------------------------------------------------------
    | 許可拡張子の説明文（UI表示用）
    |--------------------------------------------------------------------------
    */
    'allowed_extensions_description' => 'ZIP、RAR、7z、TAR、GZ、PDF、Word（doc/docx/docm）、Excel（xls/xlsx/xlsm）、PowerPoint（ppt/pptx/pptm）、一太郎（jtd/jtt）、花子（jhd）、テキスト、画像（JPG、JPEG、JFIF、PNG、GIF、BMP、SVG、WebP、TIFF、ICO、HEIC、AVIF）',

];
