<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    /**
     * 管理画面を表示
     */
    public function index()
    {
        $admin = auth()->user();
        return view('admin.index', compact('admin'));
    }
}
