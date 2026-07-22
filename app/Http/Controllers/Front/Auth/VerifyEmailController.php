<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function notice(): View
    {
        return view('front.auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $guestSessionId = $request->session()->get('cart_session_id', $request->session()->getId());

        Auth::login($user);

        $this->cartService->mergeGuestCartIntoUserCart($user, $guestSessionId);

        return redirect()
            ->to($user->defaultHomeUrl())
            ->with('status', 'メール認証が完了しました。');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()->where('email', strtolower(trim($validated['email'])))->first();

        if ($user === null) {
            return back()->with('status', '確認メールを送信しました。');
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'このメールアドレスは既に認証済みです。ログインしてください。');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', '確認メールを再送しました。');
    }
}
