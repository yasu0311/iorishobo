<?php

namespace App\Http\Controllers\Member\Buy;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CheckoutController extends Controller
{
    /**
     * STEP 2-1: 注文内容入力フォーム表示
     */
    public function create(Request $request)
    {
        // 表示用: 商品は product_number を優先、未指定時は product_id で互換
        $productNumber = $request->query('product_number');
        $productId = $request->query('product_id');
        $usage = $request->query('usage');

        $product = null;
        if ($productNumber) {
            $product = Product::where('product_number', $productNumber)->first();
        } elseif ($productId) {
            $product = Product::find($productId);
        }

        if (!$product) {
            return redirect()->route('member.buy.products.index')
                ->with('error', $productNumber || $productId ? '指定された商品が存在しません。' : '商品を選択してください。');
        }

        // 販売中・ショップ開店中でない場合は購入不可
        if (!$product->isAvailable()) {
            return redirect()->route('member.buy.products.show', $product)
                ->with('error', 'この商品は現在ご購入いただけません。販売が停止されているか、ショップが閉店しています。');
        }
        if ($this->isSelfOwnedProduct($product)) {
            return redirect()->route('member.buy.products.show', $product)
                ->with('error', 'ご自身が出品した商品は購入できません。');
        }

        $validUsages = [1, 2, 3];
        if ($usage === null || $usage === '' || !in_array((int) $usage, $validUsages, true)) {
            return redirect()->route('member.buy.products.show', $product)
                ->with('error', '利用区分（個人利用・学校利用・商用利用）を選択してから購入手続きへ進んでください。');
        }
        $usage = (int) $usage;

        // 現在の残高を取得
        $balance = auth()->user()->member->getCurrentBalance();
        return view('member.buy.checkout.create', compact('product', 'usage', 'balance'));
    }

    /**
     * STEP 2-2: 注文データを一時保存（PRGパターン）
     */
    public function store(OrderRequest $request)
    {
        $validatedData = $request->validated();
        $member = auth()->user()->member;
        $product = Product::findOrFail($validatedData['product_id']);

        // 販売停止・販売終了・ショップ閉店の場合は注文不可
        if (!$product->isAvailable()) {
            return back()->withInput()->with('error', '大変申し訳ございませんが、ご注文手続き中に商品の販売が停止されたため、ご購入いただけません。');
        }
        if ($this->isSelfOwnedProduct($product)) {
            return redirect()->route('member.buy.products.show', ['product' => $product])
                ->with('error', 'ご自身が出品した商品は購入できません。');
        }

        $product_name = $product->product_name;

        $price = match ($validatedData['usage']) {
            '1' => $product->price_for_personal,
            '2' => $product->price_for_school,
            '3' => $product->price_for_commercial,
        };

        $tax_rate = $product->shop->getConsumptionTaxRate();

        // create 表示時点の単価・税率と現在の商品が一致するか検証（出品者による変更を検知）
        $submittedPrice = (int) $validatedData['price'];
        $submittedTaxRate = (float) $validatedData['tax_rate'];
        if ($submittedPrice !== (int) $price || round($submittedTaxRate, 5) !== round($tax_rate, 5)) {
            return redirect()->route('member.buy.products.show', ['product' => $product])
                ->with('error', 'ご注文手続き中に商品価格が変更されました。大変申し訳ございませんが、再度商品ページから購入手続きをお願いします。')
                ->with('price_updated', true);
        }

        $quantity = $validatedData['quantity'];
        $tax_amount = round($price * $quantity * $tax_rate);
        $total_amount = $price * $quantity + $tax_amount;
        $points_paid = (int) $validatedData['points_paid'];
        $amount_paid = $total_amount - $points_paid;
        $transaction_fee_rate = $product->shop->transaction_fee_rate;
        $transaction_fee = round($total_amount * $transaction_fee_rate);

        // 保存前に残高を再取得し、残高超過でないか二重チェック
        $currentBalance = $member->getCurrentBalance();
        if ($points_paid > $currentBalance) {
            return back()->withInput()->withErrors([
                'points_paid' => '残高利用は現在の残高（' . number_format($currentBalance) . '円）を超えることはできません。',
            ]);
        }

        $order = DB::transaction(function () use (
            $member,
            $product,
            $validatedData,
            $product_name,
            $price,
            $quantity,
            $tax_rate,
            $tax_amount,
            $total_amount,
            $points_paid,
            $amount_paid,
            $transaction_fee,
            $request
        ) {
            // 二重送信防止: 直近の同一 pending 注文があれば再利用する
            $existingPendingOrder = Order::query()
                ->where('member_id', $member->id)
                ->where('product_id', $product->id)
                ->where('status', 'pending')
                ->whereNull('canceled_at')
                ->where('usage', $validatedData['usage'])
                ->where('licence', $validatedData['licence'])
                ->where('price', $price)
                ->where('quantity', $quantity)
                ->where('tax_rate', $tax_rate)
                ->where('tax_amount', $tax_amount)
                ->where('total_amount', $total_amount)
                ->where('points_paid', $points_paid)
                ->where('amount_paid', $amount_paid)
                ->where('transaction_fee', $transaction_fee)
                ->where('created_at', '>=', Carbon::now()->subMinutes(2))
                ->latest('id')
                ->first();

            if ($existingPendingOrder) {
                return $existingPendingOrder;
            }

            return Order::create([
                'member_id' => $member->id,
                'product_id' => $product->id,
                'product_name' => $product_name,
                'usage' => $validatedData['usage'],
                'licence' => $validatedData['licence'],
                'price' => $price,
                'quantity' => $quantity,
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'points_paid' => $points_paid,
                'amount_paid' => $amount_paid,
                'transaction_fee' => $transaction_fee,
                'ordered_at' => now(),
                'remark' => $validatedData['remark'] ?? null,
                'token' => null,
                'status' => 'pending',
                'canceled_at' => null,
                'payment_method' => null,
                'transaction_id' => null,
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('member.buy.checkout.confirm', ['order' => $order]);
    }

    /**
     * STEP 3-1: 注文確認・決済画面表示
     */
    public function confirm(Order $order)
    {
        if ($order->member_id !== auth()->user()->member->id) {
            return redirect()->route('home')->with('error', '不正なアクセスです。');
        }

        // pending状態の注文のみ表示
        if ($order->status !== 'pending') {
            return redirect()->route('member.buy.products.show', ['product' => $order->product])
                ->with('error', 'この注文は既に処理済みです。');
        }

        // 商品が販売停止・販売終了・ショップ閉店の場合はこの注文で決済不可
        $order->load('product.shop');
        if (!$order->product->isAvailable()) {
            return redirect()->route('member.buy.products.show', ['product' => $order->product])
                ->with('error', '大変申し訳ございませんが、ご注文手続き中に商品の販売が停止されたため、ご購入いただけません。');
        }
        if ($this->isSelfOwnedProduct($order->product)) {
            return redirect()->route('member.buy.products.show', ['product' => $order->product])
                ->with('error', 'ご自身が出品した商品は購入できません。');
        }

        // 価格・税率・手数料が注文時から変更されていないか再検証
        if (!$this->orderAmountsMatchCurrent($order)) {
            return redirect()->route('member.buy.products.show', ['product' => $order->product])
                ->with('error', 'ご注文手続き中に商品価格が変更されました。大変申し訳ございませんが、再度商品ページから購入手続きをお願いします。')
                ->with('price_updated', true);
        }

        // Square Web Payments SDK 用の設定値をビューへ
        $applicationId = config('services.square.application_id');
        $locationId = config('services.square.location_id');

        if (!$applicationId || !$locationId) {
            Log::error('Square設定値が未設定: application_id または location_id');
            return back()->with('error', '決済の準備中にエラーが発生しました。解消しない場合は、お問い合わせフォームからサイト運営者へご連絡ください。');
        }

        $environment = config('services.square.environment', 'sandbox');

        return view('member.buy.checkout.confirm', [
            'order' => $order,
            'squareApplicationId' => $applicationId,
            'squareLocationId' => $locationId,
            'squareEnvironment' => $environment,
        ]);
    }

    /**
     * STEP 3-2: 決済処理の確定
     */
    public function process(Request $request)
    {
        // まず注文IDのみ検証
        $validatedOrderId = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($validatedOrderId['order_id']);

        if ($order->member_id !== auth()->user()->member->id) {
            return $this->processErrorResponse($request, '不正なアクセスです。', route('home'));
        }

        if ($order->status !== 'pending') {
            return $this->processErrorResponse(
                $request,
                'この注文は既に処理済みです。',
                route('member.buy.products.show', ['product' => $order->product])
            );
        }

        // 商品が販売停止・販売終了・ショップ閉店の場合は決済不可
        $order->load('product.shop');
        if (!$order->product->isAvailable()) {
            return $this->processErrorResponse(
                $request,
                '大変申し訳ございませんが、ご注文手続き中に商品の販売が停止されたため、ご購入いただけません。',
                route('member.buy.products.show', ['product' => $order->product])
            );
        }
        if ($this->isSelfOwnedProduct($order->product)) {
            return $this->processErrorResponse(
                $request,
                'ご自身が出品した商品は購入できません。',
                route('member.buy.products.show', ['product' => $order->product])
            );
        }

        // 価格・税率・手数料が注文時から変更されていないか再検証
        if (!$this->orderAmountsMatchCurrent($order)) {
            return $this->processErrorResponse(
                $request,
                'ご注文手続き中に商品価格が変更されました。大変申し訳ございませんが、再度商品ページから購入手続きをお願いします。',
                route('member.buy.products.show', ['product' => $order->product])
            );
        }

        // ポイント利用時はその時点の残高を再チェック（0円決済・カード決済いずれも）
        if ($order->points_paid > 0) {
            $currentBalance = auth()->user()->member->getCurrentBalance();
            if ($order->points_paid > $currentBalance) {
                // 0円決済フォームなど通常POSTの場合は確認画面に戻し、
                // ユーザーに自分の操作で商品ページへ遷移してもらう
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => '残高が不足しています。残高をご確認のうえ、商品ページから再度ご注文手続きを行ってください。',
                        'redirect_url' => route('member.buy.checkout.confirm', ['order' => $order]),
                    ], 422);
                }

                return redirect()
                    ->route('member.buy.checkout.confirm', ['order' => $order])
                    ->with('error', '残高が不足しています。残高をご確認のうえ、商品ページから再度ご注文手続きを行ってください。')
                    ->with('insufficient_balance', true);
            }
        }

        // 支払金額が 0 円の場合はクレジットカード決済不要で即時完了
        if ((int) $order->amount_paid <= 0) {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending') {
                    return;
                }

                $lockedOrder->update([
                    'status' => 'completed',
                    'payment_method' => $lockedOrder->points_paid > 0 ? 'points' : 'free',
                    'transaction_id' => null,
                    'paid_at' => now(),
                ]);
            });

            return redirect()->route('member.buy.checkout.complete', ['order' => $order]);
        }

        // ここからはクレジットカード決済（Square）必須
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'source_id' => 'required|string', // Squareのトークン
        ]);

        try {
            $client = $this->getSquareClient();

            $amount = (int) $order->amount_paid; // 円を最小単位に（JPYはそのまま）
            if ($amount <= 0) {
                return $this->processErrorResponse(
                    $request,
                    '決済金額が不正です。',
                    route('member.buy.checkout.confirm', ['order' => $order])
                );
            }

            $idempotencyKey = (string) Str::uuid();

            $payload = [
                'idempotency_key' => $idempotencyKey,
                'source_id' => $validated['source_id'],
                'amount_money' => [
                    'amount' => $amount,
                    'currency' => 'JPY',
                ],
                'location_id' => config('services.square.location_id'),
                'reference_id' => (string)$order->id,
                'note' => 'Order #' . $order->id,
            ];

            $response = $client->post('/v2/payments', $payload);

            if ($response->successful()) {
                $payment = $response->json('payment');

                // ここではcompletedにせず、Webhookで最終確定する
                DB::transaction(function () use ($order, $payment) {
                    $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                    if ($lockedOrder->status !== 'pending') {
                        return;
                    }

                    $lockedOrder->update([
                        'status' => 'processing',
                        'payment_method' => 'square',
                        'transaction_id' => $payment['id'] ?? null,
                    ]);
                });

                return redirect()->route('member.buy.checkout.complete', ['order' => $order]);
            }

            Log::error('Square決済エラー', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            $errorMessage = $response->json('errors.0.detail') ?? '決済に失敗しました。';
            return $this->processErrorResponse(
                $request,
                $errorMessage,
                route('member.buy.checkout.confirm', ['order' => $order])
            );

        } catch (\Throwable $e) {
            Log::error('Square決済例外: ' . $e->getMessage());
            return $this->processErrorResponse(
                $request,
                '決済処理中にエラーが発生しました。',
                route('member.buy.checkout.confirm', ['order' => $order])
            );
        }
    }

    /**
     * STEP 4: 決済完了画面表示
     */
    public function complete(Order $order)
    {
        if ($order->member_id !== auth()->user()->member->id) {
            return redirect()->route('home')->with('error', '不正なアクセスです。');
        }

        // 決済完了または処理中のみ表示。pending の場合はお支払い手続きへ誘導
        if ($order->status === 'pending') {
            return redirect()->route('member.buy.checkout.confirm', ['order' => $order])
                ->with('error', 'この注文はまだお支払いが完了していません。お支払い手続きへ進んでください。');
        }

        if (!in_array($order->status, ['completed', 'processing'], true)) {
            return redirect()->route('member.buy.products.index')
                ->with('error', 'この注文は表示できません。');
        }

        return view('member.buy.checkout.complete', compact('order'));
    }

    /**
     * 注文に保存された金額が、現在の商品単価・税率・手数料率と一致するか検証する。
     * 価格・税率・手数料率の変更があった場合に false を返す。
     */
    private function orderAmountsMatchCurrent(Order $order): bool
    {
        $product = $order->product;
        if (!$product || !$product->relationLoaded('shop')) {
            $order->load('product.shop');
            $product = $order->product;
        }
        if (!$product?->shop) {
            return false;
        }

        $price = match ((int) $order->usage) {
            1 => $product->price_for_personal,
            2 => $product->price_for_school,
            3 => $product->price_for_commercial,
            default => null,
        };
        if ($price === null) {
            return false;
        }

        $quantity = (int) $order->quantity;
        $tax_rate = $product->shop->getConsumptionTaxRate();
        $tax_amount = (int) round($price * $quantity * $tax_rate);
        $total_amount = $price * $quantity + $tax_amount;
        $transaction_fee_rate = (float) $product->shop->transaction_fee_rate;
        $transaction_fee = (int) round($total_amount * $transaction_fee_rate);

        if ((int) $order->price !== (int) $price) {
            return false;
        }
        if (round((float) $order->tax_rate, 5) !== round($tax_rate, 5)) {
            return false;
        }
        if ((int) $order->tax_amount !== $tax_amount) {
            return false;
        }
        if ((int) $order->total_amount !== $total_amount) {
            return false;
        }
        if ((int) $order->transaction_fee !== $transaction_fee) {
            return false;
        }

        return true;
    }

    /**
     * 決済処理（process）のエラー応答。AJAX の場合は JSON、通常リクエストの場合はリダイレクト＋フラッシュ。
     */
    private function processErrorResponse(Request $request, string $message, string $redirectUrl)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'redirect_url' => $redirectUrl], 422);
        }
        return redirect($redirectUrl)->with('error', $message);
    }

    private function isSelfOwnedProduct(Product $product): bool
    {
        $product->loadMissing('shop');
        $currentMemberId = auth()->user()?->member?->id;
        if (!$currentMemberId || !$product->shop) {
            return false;
        }

        return (int) $product->shop->member_id === (int) $currentMemberId;
    }

    private function getSquareClient()
    {
        $environment = config('services.square.environment') === 'production'
            ? 'production'
            : 'sandbox';

        $baseUrl = $environment === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        $accessToken = config('services.square.access_token');

        return Http::withHeaders([
            'Square-Version' => '2024-10-17',
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->baseUrl($baseUrl);
    }
}