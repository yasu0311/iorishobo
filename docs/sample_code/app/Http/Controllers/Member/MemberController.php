<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Member $member)
    {
        return view('member.members.show', compact('member'));
    }

}
