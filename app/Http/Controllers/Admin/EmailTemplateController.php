<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::orderBy('slug')->get(),
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:500', $this->plainTextRule()],
            'body' => ['required', 'string', $this->plainTextRule()],
        ]);

        $emailTemplate->update($validated);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('status', 'メールテンプレートを更新しました。');
    }

    /**
     * Blade 構文を拒否（差し込みはアプリ側のビューで行う）
     */
    private function plainTextRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            if (preg_match('/\{\{|\{!!|@(?:if|endif|else|elseif|foreach|endforeach|forelse|endforelse|isset|empty|php|endphp|unless|endunless|for|while|switch|include|extends|section|yield|json|class)\b/', $value)) {
                $fail('Blade構文は使用できません。注文番号などの差し込み項目はシステム側で自動挿入されます。');
            }
        };
    }
}
