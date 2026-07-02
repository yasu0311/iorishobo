<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class StaticController extends Controller
{
    /**
     * 著作権（購入者向け）
     */
    public function copyrightPurchaser()
    {
        return view('static.copyright-purchaser');
    }

    /**
     * 著作権（ショップ向け）
     */
    public function copyrightShop()
    {
        return view('static.copyright-shop');
    }

    /**
     * FAQ
     */
    public function faq()
    {
        $balanceExpiryMonths = Setting::getValue('BALANCE_EXPIRY_MONTHS') ?? 6;
        $balanceReminderFirstBeforeDays = Setting::getValue('BALANCE_REMINDER_FIRST_BEFORE_DAYS') ?? 30;
        $balanceReminderSecondBeforeDays = Setting::getValue('BALANCE_REMINDER_SECOND_BEFORE_DAYS') ?? 7;

        return view('static.faq', compact(
            'balanceExpiryMonths',
            'balanceReminderFirstBeforeDays',
            'balanceReminderSecondBeforeDays'
        ));
    }

    /**
     * 手数料
     */
    public function fee()
    {
        return view('static.fee');
    }

    /**
     * 購入方法
     */
    public function howToBuy()
    {
        return view('static.how-to-buy');
    }

    /**
     * 販売方法
     */
    public function howToSell()
    {
        return view('static.how-to-sell');
    }

    /**
     * 特定商取引法
     */
    public function law()
    {
        return view('static.law');
    }
        /**
     * 利用規約
     */
    public function terms()
    {
        $balanceExpiryMonths = Setting::getValue('BALANCE_EXPIRY_MONTHS') ?? 6;
        return view('static.terms', compact('balanceExpiryMonths'));
    }

    /**
     * プライバシーポリシー
     */
    public function privacyPolicy()
    {
        return view('static.privacy-policy');
    }
}
