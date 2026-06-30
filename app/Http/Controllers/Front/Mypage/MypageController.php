<?php

namespace App\Http\Controllers\Front\Mypage;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class MypageController extends Controller
{
    public function index(): View
    {
        return view('front.mypage.index');
    }
}
