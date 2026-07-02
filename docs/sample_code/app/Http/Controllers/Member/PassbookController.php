<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PassbookController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;

        // 取引明細と残高を取得
        $result = $member->getTransactions();
        $transactions = $result['transactions'];
        $balance = $result['balance'];

        // === ページネーション ===
        $perPage = $request->input('count', 10);
        $page = $request->input('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $transactions->forPage($page, $perPage),
            $transactions->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        return view('member.passbook', [
            'transactions' => $paginated,
            'currentBalance' => number_format($balance),
        ]);
    }
}