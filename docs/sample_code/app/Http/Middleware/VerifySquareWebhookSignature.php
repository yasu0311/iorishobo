<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifySquareWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signatureKey = config('services.square.webhook_signature_key');
        $signatureHeader = $request->header('X-Square-Signature');

        if (!$signatureKey || !$signatureHeader) {
            Log::warning('Missing Square Webhook signature key or header.');
            return new Response('Missing configuration or signature', 400);
        }

        $rawPayload = $request->getContent();

        /**
         * Squareの仕様：
         * 署名は「Webhook URL（https含む）」＋「Body」を連結して計算する。
         * APP_URL と Webhook受信用URLを分離できるよう、専用URLを優先して使う。
         */
        $notificationUrl = rtrim(
            config('services.square.webhook_notification_url')
                ?: (rtrim(config('app.url'), '/') . '/api/webhook/square'),
            '/'
        );

        $calculatedSignature = base64_encode(
            hash_hmac('sha1', $notificationUrl . $rawPayload, $signatureKey, true)
        );

        if (!hash_equals($calculatedSignature, $signatureHeader)) {
            Log::error('Square Webhook signature verification failed. The request may be spoofed.', [
                'received_signature' => $signatureHeader,
                'calculated_signature' => $calculatedSignature,
                'used_url' => $notificationUrl,
            ]);
            return new Response('Invalid Signature', 403);
        }

        // ✅ 検証に成功したら次の処理へ
        return $next($request);
    }
}

// class VerifySquareWebhookSignature
// {
// /**
//      * Square Webhookの署名を検証します。
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
//      * @return \Symfony\Component\HttpFoundation\Response
//      */
//     public function handle(Request $request, Closure $next): Response
//     {
//         // 1. Square Webhook Signature Keyの取得
//         // このキーは config/services.php または .env で設定されている必要があります
//         $signatureKey = config('services.square.webhook_signature_key');

//         if (empty($signatureKey)) {
//             Log::error('Square Webhook Signature Key (services.square.webhook_signature_key) is missing.');
//             // 本番環境では設定エラーとして拒否すべき
//             return new Response('Server configuration error.', 500);
//         }

//         // 2. 署名ヘッダーの取得
//         $signatureHeader = $request->header('X-Square-Signature');

//         if (!$signatureHeader) {
//             Log::warning('Square Webhook received without X-Square-Signature header. Rejected.');
//             return new Response('Missing Signature', 400);
//         }

//         // 3. 生のリクエストボディの取得
//         // Squareの検証には、JSONデコード前の生のバイトデータが必要です
//         $rawPayload = $request->getContent();
        
//         // 4. 署名の計算 (HMAC-SHA1)
//         // Base64(HMAC-SHA1(raw_payload, signature_key)) を計算します
//         $calculatedSignature = base64_encode(hash_hmac('sha1', $rawPayload, $signatureKey, true));

//         // 5. 署名の検証
//         // hash_equals() を使用してタイミング攻撃を防ぎます
//         if (!hash_equals($calculatedSignature, $signatureHeader)) {
//             Log::error('Square Webhook signature verification failed. The request may be spoofed.', [
//                 'received_signature' => $signatureHeader,
//                 'calculated_signature' => $calculatedSignature,
//             ]);
//             return new Response('Invalid Signature', 403);
//         }

//         // 検証に成功した場合のみ、次の処理へ
//         return $next($request);
//     }
// }

