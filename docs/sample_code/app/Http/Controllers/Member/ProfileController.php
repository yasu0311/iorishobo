<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Requests\StoreMemberRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        if ($user && $user->member) {
            return redirect()->route('member.profile.edit')
                ->withErrors(['error' => '既に会員情報が登録されています。編集画面をご利用ください。']);
        }

        return view('member.profile.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMemberRequest $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('ユーザーが認証されていません。');
            }

            // 既に会員情報が存在する場合はエラー
            if ($user->member) {
                return redirect()->route('member.profile.edit')
                    ->withErrors(['error' => '既に会員情報が登録されています。編集画面をご利用ください。']);
            }

            $validated = $request->validated();
            
            // アイコン画像のアップロード処理
            if ($request->hasFile('member_icon')) {
                $path = $request->file('member_icon')->store('member_icons', 'public');
                $validated['member_icon'] = $path;
            }
            
            // ユーザーIDを追加
            $validated['user_id'] = $user->id;

            // 会員情報登録時点のクライアントIP（クライアントからは送らせない）
            $validated['ip_address'] = $request->ip();
            
            // 会員情報を作成
            $member = Member::create($validated);

            return redirect()->route('member.profile.show')->with('success', '会員情報を登録しました。');

        } catch (\Exception $e) {
            Log::error('Member profile store failed', ['exception' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => '会員情報の登録に失敗しました。時間をおいて再度お試しください。解消しない場合はお問い合わせフォームからご連絡ください。']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {

        $member = Auth::user()?->member;
        if(!$member){
            return redirect()->route('member.profile.create');
        }

        return view('member.profile.show', compact('member'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                throw new \Exception('ユーザーが認証されていません。');
            }
            $member = $user->member;
            if (!$member) {
                throw new \Exception('会員情報が存在しません。');
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => '会員情報が存在しません。']);
        }

        return view('member.profile.edit', compact('member'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                throw new \Exception('ユーザーが認証されていません。');
            }
            $member = $user->member;
            if (!$member) {
                throw new \Exception('会員情報が存在しません。');
            }

            $validated = $request->validated();

            // 口座名義チェックのため、名称変更・代表者氏名変更・法人解散に関連する項目は更新しない
            $restrictedFields = [
                'company', 'company_name', 'company_name_kana',
                'last_name', 'first_name', 'last_name_kana', 'first_name_kana',
            ];
            $validated = collect($validated)->except($restrictedFields)->toArray();

            // アイコン画像のアップロード処理
            if ($request->hasFile('member_icon')) {
                // 既存の画像を削除
                if ($member->member_icon && Storage::disk('public')->exists($member->member_icon)) {
                    Storage::disk('public')->delete($member->member_icon);
                }

                $path = $request->file('member_icon')->store('member_icons', 'public');
                $validated['member_icon'] = $path;
            }

            $member->fill($validated);
            $member->save();

            return redirect()->route('member.profile.show')->with('success', '会員情報を更新しました。');

        } catch (\Exception $e) {
            Log::error('Member profile update failed', ['exception' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => '会員情報の更新に失敗しました。時間をおいて再度お試しください。解消しない場合はお問い合わせフォームからご連絡ください。']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
