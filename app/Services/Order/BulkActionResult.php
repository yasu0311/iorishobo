<?php

namespace App\Services\Order;

use App\Models\Order;

class BulkActionResult
{
    /**
     * @param  list<Order>  $succeeded
     * @param  list<array{order: Order, reason: string}>  $skipped
     * @param  list<array{order: Order, reason: string}>  $mailFailed
     */
    public function __construct(
        public readonly array $succeeded,
        public readonly array $skipped,
        public readonly array $mailFailed = [],
    ) {}

    public function succeededCount(): int
    {
        return count($this->succeeded);
    }

    public function skippedCount(): int
    {
        return count($this->skipped);
    }

    public function mailFailedCount(): int
    {
        return count($this->mailFailed);
    }
}
