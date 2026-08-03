<?php

namespace App\Http\Controllers\Front\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\ProfileUpdateRequest;
use App\Services\Mypage\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function edit(): View
    {
        $user = Auth::user();
        $user?->load('customer');

        return view('front.mypage.profile', compact('user'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        $result = $this->profileService->update($user, $request->validated());

        if ($result['email_change_requested']) {
            return redirect()
                ->route('mypage.profile.edit')
                ->with('status', '確認メールを新しいアドレスに送信しました。認証が完了するまで、ログイン用メールは現在のアドレスのままです。');
        }

        return redirect()
            ->route('mypage.profile.edit')
            ->with('status', 'プロフィールを更新しました。');
    }

    public function cancelPendingEmail(): RedirectResponse
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        $this->profileService->cancelPendingEmail($user);

        return redirect()
            ->route('mypage.profile.edit')
            ->with('status', 'メールアドレスの変更申請を取り消しました。');
    }

    public function resendPendingEmail(): RedirectResponse
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        if (! filled($user->pending_email)) {
            return redirect()
                ->route('mypage.profile.edit')
                ->with('status', '変更申請中のメールアドレスはありません。');
        }

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('mypage.profile.edit')
            ->with('status', '確認メールを再送しました。');
    }
}
