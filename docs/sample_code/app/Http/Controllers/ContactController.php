<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\ContactRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\Contact\ContactNotification;
use App\Mail\Contact\ContactAdminNotification;

class ContactController extends Controller
{
    /**
     * 入力フォーム表示。
     * 確認画面から「内容を修正する」で戻った場合のみ入力内容を復元する（contact_came_from_back フラグで判定）。
     * それ以外（別ページから直接アクセス等）で来た場合は contact_input をクリアし、空のフォームを表示する。
     * ログイン済みの場合は会員の名前・メールアドレスを事前入力する。
     */
    public function create(Request $request)
    {
        if ($request->session()->pull('contact_came_from_back', false)) {
            $contact = $request->session()->get('contact_input', []);
        } else {
            $request->session()->forget('contact_input');
            $contact = [];

            // ログイン時は会員の名前・メールアドレスを事前入力
            $user = Auth::user();
            if ($user) {
                $contact['email'] = $user->email ?? '';
                $member = $user->member;
                if ($member) {
                    $fullName = trim(($member->last_name ?? '') . ' ' . ($member->first_name ?? ''));
                    if ($fullName === '') {
                        $fullName = $member->nickname ?? $user->name ?? '';
                    }
                    $contact['name'] = $fullName;
                } else {
                    $contact['name'] = $user->name ?? '';
                }
            }

            $inquiryType = $request->query('inquiry_type');
            if (is_string($inquiryType) && in_array($inquiryType, Contact::getInquiryTypes(), true)) {
                $contact['inquiry_type'] = $inquiryType;
            }

            $message = $request->query('message');
            if (is_string($message) && trim($message) !== '') {
                $contact['message'] = $message;
            }
        }

        return view('contact.create', compact('contact'));
    }

    public function confirm(ContactRequest $request)
    {
        $contact = $request->validated();
        $request->session()->put('contact_input', $contact);

        return view('contact.confirm', compact('contact'));
    }

    public function store(Request $request)
    {
        $data = $request->session()->get('contact_input', []);

        if (empty($data)) {
            return redirect()->route('contacts.create')
                ->with('error', 'セッションが切れました。再度入力してください。');
        }

        $validator = Validator::make($data, (new ContactRequest())->rules(), (new ContactRequest())->messages());
        if ($validator->fails()) {
            return redirect()->route('contacts.create')
                ->withErrors($validator)
                ->withInput($data);
        }
        $data = $validator->validated();

        $contact = Contact::create([
            'member_id' => Auth::user()?->member?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'inquiry_type' => $data['inquiry_type'],
            'message' => $data['message'],
            'ip_address' => $request->ip(),
        ]);

        // 送信者へ自動返信メール（キュー経由で非同期送信）
        try {
            Mail::to($contact->email)->queue(new ContactNotification($contact));

            // 管理者へ通知メール（role=1 の全ユーザー）
            $admins = User::where('role', 1)->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(new ContactAdminNotification($contact));
            }
        } catch (\Throwable $e) {
            Log::error('Contact mail queue failed', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
        }

        $request->session()->forget('contact_input');
        $request->session()->put('contact_sent', true);

        return redirect()->route('contacts.complete');
    }

    public function complete(Request $request)
    {
        if (!$request->session()->has('contact_sent')) {
            return redirect()->route('contacts.create');
        }

        $request->session()->forget('contact_sent');

        return view('contact.complete');
    }

    /**
     * 確認画面から入力画面へ戻る。
     * contact_came_from_back フラグを立ててリダイレクトし、create() で「戻る」時のみ入力内容を復元するようにする。
     */
    public function back(Request $request)
    {
        $request->session()->put('contact_came_from_back', true);

        return redirect()->route('contacts.create');
    }
}
