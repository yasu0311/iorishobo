<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanupGuestCarts extends Command
{
    protected $signature = 'carts:cleanup-guest {--days=90 : 更新から何日経過したゲストカートを削除するか}';

    protected $description = '90 日以上更新のないゲストカートを削除する';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = Cart::query()
            ->whereNull('user_id')
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        $this->info("削除したゲストカート: {$deleted} 件");

        return self::SUCCESS;
    }
}
