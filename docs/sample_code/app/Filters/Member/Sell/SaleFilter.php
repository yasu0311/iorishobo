<?php

namespace App\Filters\Member\Sell;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class SaleFilter
{
    protected $query;
    protected $request;

    // 表示件数の選択肢
    public const PER_PAGE_OPTIONS = [
        10 => '10件',
        20 => '20件',
        50 => '50件',
    ];
    public const DEFAULT_PER_PAGE = 10;

    // 並び順の選択肢
    public const SORT_OPTIONS = [
        'new' => '新しい順',
        'old' => '古い順',
    ];
    public const DEFAULT_SORT = 'new';

    // 利用法の選択肢
    public const USAGE_OPTIONS = [
        ''  => 'すべて',
        '1' => '個人',
        '2' => '学校',
        '3' => '商用',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($query)
    {
        $this->query = $query instanceof Builder ? $query : $query;

        foreach ($this->filters() as $name => $value) {
            $method = 'filter' . ucfirst($name);
            if (method_exists($this, $method) && $value !== null && $value !== '') {
                $this->$method($value);
            }
        }

        // 並び順の適用
        $this->applySort();

        return $this->query;
    }

    protected function filters()
    {
        return $this->request->only([
            'product_id',
            'usage',
            'order_id',
            'customer',
            'start_date',
            'end_date',
        ]);
    }

    // 各条件ごとのメソッド

    protected function filterProduct_id($value)
    {
        $this->query->where('product_id', (int) $value);
    }

    protected function filterUsage($value)
    {
        $this->query->where('usage', (int) $value);
    }

    protected function filterOrder_id($value)
    {
        $this->query->where('order_number', $value);
    }

    protected function filterCustomer($value)
    {
        $keyword = trim($value);
        if ($keyword === '') {
            return;
        }
        $this->query->whereHas('member', function ($q) use ($keyword) {
            $q->where('nickname', 'like', "%{$keyword}%")
              ->orWhere('last_name', 'like', "%{$keyword}%")
              ->orWhere('first_name', 'like', "%{$keyword}%");
        });
    }

    protected function filterStart_date($value)
    {
        $this->query->whereDate('ordered_at', '>=', $value);
    }

    protected function filterEnd_date($value)
    {
        $this->query->whereDate('ordered_at', '<=', $value);
    }

    /**
     * 並び順を適用
     */
    protected function applySort()
    {
        $sort = $this->request->input('sort_order', self::DEFAULT_SORT);

        if (!array_key_exists($sort, self::SORT_OPTIONS)) {
            $sort = self::DEFAULT_SORT;
        }

        if ($sort === 'old') {
            $this->query->orderBy('ordered_at', 'asc');
        } else {
            $this->query->orderBy('ordered_at', 'desc');
        }
    }

    /**
     * 1ページあたりの件数を取得（バリデーション付き）
     */
    public function getPerPage()
    {
        $perPage = $this->request->input('display_count', self::DEFAULT_PER_PAGE);

        if (!array_key_exists($perPage, self::PER_PAGE_OPTIONS)) {
            return self::DEFAULT_PER_PAGE;
        }

        return (int) $perPage;
    }

    /**
     * ビューに渡す固定選択肢
     */
    public function getViewData()
    {
        return [
            'usage'         => self::USAGE_OPTIONS,
            'display_count' => self::PER_PAGE_OPTIONS,
            'sort_order'    => self::SORT_OPTIONS,
        ];
    }
}
