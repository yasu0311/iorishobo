<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirectController extends Controller
{
    /**
     * トップページ。カラーミー旧 URL（?pid=）は 301 で商品詳細へ転送する。
     */
    public function home(Request $request): View|RedirectResponse
    {
        if ($request->filled('pid')) {
            return $this->redirectByProductId($request->query('pid'));
        }

        return view('welcome');
    }

    /**
     * カラーミー商品 ID から /products/{slug} へ 301 リダイレクトする。
     */
    public function redirectByProductId(mixed $pid): RedirectResponse
    {
        $id = (string) $pid;

        if ($id === '' || ! ctype_digit($id)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $product = Product::query()
            ->where(function ($query) use ($id) {
                $query->where('colorme_product_id', (int) $id)
                    ->orWhere('slug', $id);
            })
            ->first();

        if ($product === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return redirect(route('products.show', $product->slug), Response::HTTP_MOVED_PERMANENTLY);
    }
}
