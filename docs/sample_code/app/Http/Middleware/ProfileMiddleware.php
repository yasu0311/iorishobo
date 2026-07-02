<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // membersテーブルにデータがあるかチェック
        if (!$user->member) {
            return redirect()->route('member.profile.create')->with('error', '会員限定機能を利用する際には会員情報登録を完了する必要があります。'); 
        }
    
        return $next($request);

    }
}
