<?php

namespace App\Http\Controllers\Member\Sell;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Filters\Member\Sell\SaleFilter;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $filter = new SaleFilter($request);

        $builder = Order::query()
            ->active()
            ->with(['product', 'member']);

        // フィルタ適用（並び順も含む）
        $sales = $filter
            ->apply($builder)
            ->whereHas('product', function ($q) {
                $q->where('shop_id', Auth::user()->member->shop->id);
            })  // 出品者の商品の売り上げのみに限定
            ->paginate($filter->getPerPage())
            ->withQueryString();
            
        // $products = Product::orderBy('product_name')->pluck('product_name', 'id');

        $options = $filter->getViewData();

        return view('member.sell.sales.index', compact('sales', 'options', 'request'));
    }

    public function show(Order $order)
    {
        //自分の店舗の注文のみ表示
        // dd(Auth::user()->member->shop->id, $order->product->shop_id);
        if(Auth::user()->member->shop->id !== $order->product->shop_id){
            abort(403, 'この注文は表示できません。');
        }
        return view('member.sell.sales.show', compact('order'));
    }

    public function summaryMonthly(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->member) {
            abort(403, '会員情報が存在しません。');
        }

        $member = $user->member;
        
        // 出品者のショップを取得
        $shop = Shop::where('member_id', $member->id)->first();
        if (!$shop) {
            return view('member.sell.sales.summary-monthly', [
                'year' => $request->input('year', date('Y')),
                'monthlyData' => [],
                'totalCount' => 0,
                'totalAmount' => 0,
                'availableYears' => [],
            ]);
        }

        // 出品者の商品IDを取得
        $productIds = Product::where('shop_id', $shop->id)->pluck('id');

        // 年を取得（デフォルトは現在の年）
        $year = (int) $request->input('year', date('Y'));

        // 月別売上を集計（DB側で集計して大量データ時の負荷を抑える）
        $startDate = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $endDate = \Carbon\Carbon::create($year, 12, 31)->endOfDay();
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "CAST(strftime('%m', ordered_at) AS INTEGER)"
            : 'MONTH(ordered_at)';

        $monthlyData = Order::whereIn('product_id', $productIds)
            ->whereBetween('ordered_at', [$startDate, $endDate])
            ->active()
            ->selectRaw("$monthExpr as month, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total_amount")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($row) => (int) $row->month);

        // 12ヶ月分のデータを準備（データがない月は0で埋める）
        $monthlyResults = [];
        $totalCount = 0;
        $totalAmount = 0;

        for ($month = 1; $month <= 12; $month++) {
            $data = $monthlyData->get($month);
            $count = $data ? (int) $data->count : 0;
            $amount = $data ? (int) $data->total_amount : 0;
            
            $monthlyResults[] = [
                'month' => $month,
                'count' => $count,
                'amount' => $amount,
            ];
            
            $totalCount += $count;
            $totalAmount += $amount;
        }

        // 利用可能な年を取得（DB集計）
        $yearExpr = $driver === 'sqlite'
            ? "CAST(strftime('%Y', ordered_at) AS INTEGER)"
            : 'YEAR(ordered_at)';
        $availableYears = Order::whereIn('product_id', $productIds)
            ->active()
            ->selectRaw("$yearExpr as year")
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($yearValue) => (int) $yearValue)
            ->values()
            ->toArray();

        // 年が選択されていない場合は、利用可能な年の最初の年を設定
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        return view('member.sell.sales.summary-monthly', [
            'year' => $year,
            'monthlyData' => $monthlyResults,
            'totalCount' => $totalCount,
            'totalAmount' => $totalAmount,
            'availableYears' => $availableYears,
        ]);
    }

    public function summaryProduct(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->member) {
            abort(403, '会員情報が存在しません。');
        }

        $member = $user->member;

        // 出品者のショップを取得
        $shop = Shop::where('member_id', $member->id)->first();
        if (!$shop) {
            return view('member.sell.sales.summary-product', [
                'selectedYear' => $request->input('year'),
                'productData' => [],
                'totalCount' => 0,
                'totalAmount' => 0,
                'availableYears' => [],
            ]);
        }

        // 出品者の商品ID
        $productIds = Product::where('shop_id', $shop->id)->pluck('id');

        $driver = DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite'
            ? "CAST(strftime('%Y', ordered_at) AS INTEGER)"
            : 'YEAR(ordered_at)';

        // 利用可能な年一覧（降順）
        $availableYears = Order::whereIn('product_id', $productIds)
            ->active()
            ->selectRaw("$yearExpr as year")
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($yearValue) => (int) $yearValue)
            ->values()
            ->toArray();

        $selectedYear = $request->input('year'); // nullまたは"YYYY"

        // 対象注文の取得（年指定があれば絞り込み）
        $ordersQuery = Order::whereIn('product_id', $productIds)
            ->active();

        if (!empty($selectedYear)) {
            $yearInt = (int) $selectedYear;
            $startDate = \Carbon\Carbon::create($yearInt, 1, 1)->startOfDay();
            $endDate = \Carbon\Carbon::create($yearInt, 12, 31)->endOfDay();
            $ordersQuery->whereBetween('ordered_at', [$startDate, $endDate]);
        }

        $aggregated = $ordersQuery
            ->selectRaw('product_id, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupBy('product_id')
            ->orderByDesc('total_amount')
            ->get();

        // 商品名を付与しつつ配列化
        $products = Product::whereIn('id', $aggregated->pluck('product_id')->all())->pluck('product_name', 'id');
        $productData = [];
        $totalCount = 0;
        $totalAmount = 0;
        foreach ($aggregated as $row) {
            $pid = (int) $row->product_id;
            $name = $products[$pid] ?? '不明な商品';
            $count = (int) $row->count;
            $amount = (int) $row->total_amount;
            $productData[] = [
                'product_name' => $name,
                'count' => $count,
                'amount' => $amount,
            ];
            $totalCount += $count;
            $totalAmount += $amount;
        }

        return view('member.sell.sales.summary-product', [
            'selectedYear' => $selectedYear,
            'productData' => $productData,
            'totalCount' => $totalCount,
            'totalAmount' => $totalAmount,
            'availableYears' => $availableYears,
        ]);
    }
}
