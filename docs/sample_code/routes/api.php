<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Http\Response;
use App\Http\Controllers\Webhook\SquareWebhookController;
use App\Http\Middleware\VerifySquareWebhookSignature;

// ここに他のAPIルートを追加

// Stripe webhook用のルート
// Route::post('/webhook/stripe', [StripeWebhookController::class, 'handleWebhook'])
//     ->name('webhook.stripe')
//     ->middleware(VerifyWebhookSignature::class)
//     ->withoutMiddleware(['throttle:api', 'api', 'auth:api']);
    
    
// Square webhook用のルート
Route::post('/webhook/square', [SquareWebhookController::class, 'handleWebhook'])
    ->name('webhook.square')
    ->middleware(VerifySquareWebhookSignature::class)
    ->withoutMiddleware(['throttle:api', 'api', 'auth:api']);