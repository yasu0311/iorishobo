<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ウォレット残高の失効バッチ
Schedule::command('wallet:expire')->dailyAt('02:00');

// 商品評価 → ランキング（注目教材の重み）。評価集計のあとに実行する
Schedule::command('products:update-ratings')->dailyAt('02:05');
Schedule::command('products:update-ranking')->dailyAt('02:10');

// DB + アップロードファイルの定期バックアップ（古い世代の削除は直後に実行）
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:15');
