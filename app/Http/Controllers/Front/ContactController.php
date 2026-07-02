<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactAdminMail;
use App\Mail\ContactReceivedMail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function create(Request $request): View
    {
        if ($request->session()->pull('contact_came_from_back', false)) {
            $contact = $request->session()->get('contact_input', []);
        } else {
            $request->session()->forget('contact_input');
            $contact = [];
        }

        $user = $request->user();

        if ($user !== null) {
            $contact['email'] = $contact['email'] ?? $user->email;
            $contact['name'] = $contact['name'] ?? $user->name;
        }

        return view('front.contact.create', compact('contact'));
    }

    public function confirm(ContactRequest $request): View
    {
        $contact = $request->validated();
        $request->session()->put('contact_input', $contact);

        return view('front.contact.confirm', compact('contact'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->session()->get('contact_input', []);

        if ($data === []) {
            return redirect()->route('contacts.create')
                ->withErrors(['message' => '入力内容が見つかりません。もう一度お試しください。']);
        }

        $recipient = config('shop.email') ?: config('mail.from.address');

        if ($recipient === null || $recipient === '') {
            return redirect()->route('contacts.create')
                ->withErrors(['message' => 'お問い合わせを送信できません。しばらくしてから再度お試しください。']);
        }

        Mail::to($recipient)->send(new ContactAdminMail($data));
        Mail::to($data['email'])->send(new ContactReceivedMail($data));

        $request->session()->forget('contact_input');
        $request->session()->put('contact_sent', true);

        return redirect()->route('contacts.complete');
    }

    public function back(Request $request): RedirectResponse
    {
        $request->session()->put('contact_came_from_back', true);

        return redirect()->route('contacts.create');
    }

    public function complete(Request $request): View|RedirectResponse
    {
        if (! $request->session()->pull('contact_sent', false)) {
            return redirect()->route('contacts.create');
        }

        return view('front.contact.complete');
    }
}
