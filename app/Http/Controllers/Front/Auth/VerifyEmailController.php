<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
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

        Auth::login($user);

        return redirect()
            ->route('mypage.index')
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
