<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;
use App\Http\Requests\PhotoRequest;
use App\Filters\PhotoFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    public function index(Request $request)
    {
        $filter = new PhotoFilter($request);
        $photos = Photo::query()
            ->filter($filter)
            ->latest()
            ->paginate(20);

        return view('photos.index', compact('photos', 'filter'));
    }

    public function confirm(PhotoRequest $request)
    {
        $validated = $request->validated();
        $request->session()->put('photo_input', $validated);

        return view('photos.confirm', compact('validated'));
    }

    public function store(PhotoRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();
        $member = $user->member;

        try {
            Photo::create([
                'member_id' => $member->id,
                'product_id' => $validated['product_id'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
                'file_size' => $validated['file_size'] ?? null,
                'file_description' => $validated['file_description'] ?? null,
                'copyright' => $validated['copyright'] ?? null,
                'macro' => $validated['macro'] ?? null,
                'file_updated_at' => now(),
                'security_check' => 0,
                'display_order' => $validated['display_order'] ?? null,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('写真の作成中にエラー: '.$e->getMessage());
            return back()->withInput()->withErrors(['投稿の保存時にエラーが発生しました。']);
        }

        return redirect()->route('photos.index')->with('success', '投稿が作成されました。');
        // 完了画面を作るとき
        // return redirect()->route('photos.complete');
    }

    public function complete()
    {
        return view('photos.complete');
    }

    public function show(Photo $photo)
    {   
        $this->authorizePhoto($photo);
        return view('photos.show', compact('photo'));
    }

    public function edit(Photo $photo)
    {
        $this->authorizePhoto($photo);
        return view('photos.edit', compact('photo'));
    }

    public function update(PhotoRequest $request, Photo $photo)
    {
        $this->authorizePhoto($photo);
        $validated = $request->validated();

        try {
            $photo->update([
                'product_id' => $validated['product_id'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
                'file_size' => $validated['file_size'] ?? null,
                'file_description' => $validated['file_description'] ?? null,
                'copyright' => $validated['copyright'] ?? null,
                'macro' => $validated['macro'] ?? null,
                'file_updated_at' => now(),
                'security_check' => 0,
                'display_order' => $validated['display_order'] ?? null,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('編集中にエラー: '.$e->getMessage());
            return back()->withInput()->withErrors(['編集保存時にエラーが発生しました。']);
        }

        return redirect()->route('photos.show', $photo)->with('success', '投稿が更新されました。');
    }

    public function destroy(Photo $photo)
    {
        $this->authorizePhoto($photo);

        try {
            $photo->delete();
        } catch (\Exception $e) {
            Log::error('削除中にエラー: '.$e->getMessage());
            return back()->withErrors(['削除時にエラーが発生しました。']);
        }

        return redirect()->route('photos.index')->with('success', '投稿が削除されました。');
    }

    /**
     * 認可チェック
     */
    private function authorizePhoto(Photo $photo)
    {
        $memberId = Auth::user()->member->id ?? null;
        if ($photo->member_id !== $memberId) {
            abort(403, 'この操作を行う権限がありません。');
        }
    }
}
