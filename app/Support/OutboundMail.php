<?php

namespace App\Support;

use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class OutboundMail
{
    /**
     * @param  mixed  $to
     * @param  Closure(): Mailable|Mailable  $mailable
     * @param  array<string, mixed>  $extra
     * @return bool true if sent successfully
     */
    public static function send(mixed $to, Closure|Mailable $mailable, string $context, array $extra = []): bool
    {
        try {
            $instance = $mailable instanceof Closure ? $mailable() : $mailable;
            Mail::to($to)->send($instance);

            return true;
        } catch (Throwable $exception) {
            Log::error('mail.send_failed', array_merge([
                'context' => $context,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ], $extra));

            return false;
        }
    }
}
