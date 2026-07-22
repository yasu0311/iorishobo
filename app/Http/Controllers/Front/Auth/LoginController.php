<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function create(): View
    {
        return view('front.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $guestSessionId = $request->session()->get('cart_session_id', $request->session()->getId());

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        $user = Auth::user();

        if ($user === null || ! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'メール認証が完了していません。登録時のメールをご確認ください。',
            ]);
        }

        $this->cartService->mergeGuestCartIntoUserCart($user, $guestSessionId);

        $request->session()->regenerate();

        return redirect()->intended($user->defaultHomeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
