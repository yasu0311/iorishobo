<?php

namespace App\Http\Requests\Mypage;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:25',
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = strtolower(trim((string) $value));
                    $userId = $this->user()?->id;

                    $taken = User::query()
                        ->where('id', '!=', $userId)
                        ->where(function ($query) use ($email) {
                            $query->where('email', $email)
                                ->orWhere('pending_email', $email);
                        })
                        ->exists();

                    if ($taken) {
                        $fail('このメールアドレスは既に使用されています。');
                    }
                },
            ],
            'name_kana' => 'nullable|string|max:25',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|size:7',
            'prefecture' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:50',
            'address_line2' => 'nullable|string|max:30',
        ];
    }
}
