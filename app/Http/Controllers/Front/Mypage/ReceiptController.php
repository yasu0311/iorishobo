<?php

namespace App\Http\Controllers\Front\Mypage;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReceiptController extends Controller
{
    use AuthorizesRequests;

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        // 領収書は書面で同封するため、マイページ上では表示しません。
        abort(404);
    }
}
