<?php

namespace App\Http\Controllers\Front;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class StaticController extends Controller
{
    public function law(): View
    {
        $paymentMethods = collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $method) => $method->isAvailableAtCheckout())
            ->map(fn (PaymentMethod $method) => $method->label())
            ->implode('、');

        return view('front.static.law', compact('paymentMethods'));
    }

    public function privacyPolicy(): View
    {
        return view('front.static.privacy-policy');
    }

    public function terms(): View
    {
        return view('front.static.terms');
    }
}
