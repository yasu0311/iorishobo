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

        if ($result['email_changed']) {
            return redirect()
                ->route('verification.notice')
                ->with('status', 'メールアドレスを変更しました。確認メールを送信したので、認証を完了してください。');
        }

        return redirect()
            ->route('mypage.profile.edit')
            ->with('status', 'プロフィールを更新しました。');
    }
}
