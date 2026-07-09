<?php

namespace App\Services\Order;

use App\Models\Order;

class BulkActionResult
{
    /**
     * @param  list<Order>  $succeeded
     * @param  list<array{order: Order, reason: string}>  $skipped
     */
    public function __construct(
        public readonly array $succeeded,
        public readonly array $skipped,
    ) {}

    public function succeededCount(): int
    {
        return count($this->succeeded);
    }

    public function skippedCount(): int
    {
        return count($this->skipped);
    }
}
