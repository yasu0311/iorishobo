<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanupGuestCarts extends Command
{
    protected $signature = 'carts:cleanup
                            {--guest-days=90 : 更新から何日経過したゲストカートを削除するか}
                            {--user-days=60 : 更新から何日経過した会員カートを削除するか}';

    protected $description = '一定期間更新のないゲスト・会員カートを削除する';

    public function handle(): int
    {
        $guestDays = (int) $this->option('guest-days');
        $userDays = (int) $this->option('user-days');

        $guestDeleted = Cart::query()
            ->whereNull('user_id')
            ->where('updated_at', '<', now()->subDays($guestDays))
            ->delete();

        $userDeleted = Cart::query()
            ->whereNotNull('user_id')
            ->where('updated_at', '<', now()->subDays($userDays))
            ->delete();

        $this->info("削除したゲストカート: {$guestDeleted} 件");
        $this->info("削除した会員カート: {$userDeleted} 件");

        return self::SUCCESS;
    }
}
