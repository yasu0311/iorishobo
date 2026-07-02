<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // shopsテーブルにデータがあるかチェック
        if (!$user?->member?->shop) {
            return redirect()->route('member.sell.shop.create')->with('error', '販売者用機能をご利用するためにはショップ作成を完了する必要があります。'); 
        }
    
        return $next($request);

    }
}
