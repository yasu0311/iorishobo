<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function create(): View
    {
        return view('front.auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $this->registrationService->register($request->validated());

        return redirect()
            ->route('verification.notice')
            ->with('status', '確認メールを送信しました。メール内のリンクから認証を完了してください。');
    }
}
