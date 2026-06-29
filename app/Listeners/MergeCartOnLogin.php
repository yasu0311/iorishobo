<?php

namespace App\Listeners;

use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Login;

class MergeCartOnLogin
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function handle(Login $event): void
    {
        $this->cartService->mergeGuestCartIntoUserCart(
            $event->user,
            session()->getId(),
        );
    }
}
