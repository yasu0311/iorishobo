<?php

namespace App\Filters\Member\Buy;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Grade;
use App\Models\Subject;

class ProductFilter
{
    protected $request;
    protected $builder;
    protected $grades;
    protected $subjects;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->grades = Grade::orderBy('display_order')->pluck('grade', 'id')->toArray();
        $this->subjects = Subject::orderBy('display_order')->pluck('subject', 'id')->toArray();
        $merge = ['usage' => $this->request->get('usage', self::DEFAULT_USAGE)];
        if ($this->request->has('price_min') && is_string($this->request->price_min)) {
            $merge['price_min'] = str_replace(',', '', $this->request->price_min);
        }
        if ($this->request->has('price_max') && is_string($this->request->price_max)) {
            $merge['price_max'] = str_replace(',', '', $this->request->price_max);
        }
        $this->request->merge($merge);
    }

    // 表示件数の選択肢
    public const PER_PAGE_OPTIONS = [
        10 => '10件',
        25 => '25件',
        50 => '50件',
        100 => '100件',
    ];
    public const DEFAULT_PER_PAGE = 10;

    // 利用方法の選択肢
    public const USAGE_OPTIONS = [
        '1' => '個人利用',
        '2' => '学校利用',
        '3' => '商用利用',
    ];
    public const DEFAULT_USAGE = '1';

    // 並び順の選択肢
    public const SORT_OPTIONS = [
        'recommend' => 'おすすめ順',
        'popular' => '売れ筋順',
        'rating' => '評価の高い順',
        'price_asc' => '価格（安い順）',
        'price_desc' => '価格（高い順）',
        'created_at_desc' => '新しい順',
        'created_at_asc' => '古い順',

    ];
    public const DEFAULT_SORT = 'recommend';

    public function apply(Builder $builder)
    {
        $this->builder = $builder;

        $this->filterName();
        $this->filterShop();
        $this->filterByGrade();
        $this->filterBySubject();
        $this->filterByPriceRange();
        $this->applySort();

        return $this->builder;
    }

    // 各条件ごとのメソッド
    protected function filterName()
    {
        if ($this->request->filled('product_name')) {
            $keyword = $this->request->input('product_name');
            $this->builder->where('product_name', 'like', "%{$keyword}%");
        }
    }

    protected function filterShop()
    {
        if ($this->request->filled('shop')) {
            $keyword = $this->request->input('shop');
            $this->builder->whereHas('shop', function ($query) use ($keyword) {
                $query->where('shop_name', 'like', "%{$keyword}%");
            });
        }
    }

    protected function filterByPriceRange()
    {
        $priceColumn = $this->getUsagePriceColumn();

        if ($this->request->boolean('free')) {
            $this->builder->where($priceColumn, 0);
            return;
        }

        if ($this->request->filled('price_min')) {
            $this->builder->where($priceColumn, '>=', (int) $this->request->input('price_min'));
        }

        if ($this->request->filled('price_max')) {
            $this->builder->where($priceColumn, '<=', (int) $this->request->input('price_max'));
        }
    }

    protected function filterByGrade()
    {
        if ($this->request->filled('grade')) {
            $gradeId = $this->request->input('grade');
            $this->builder->whereHas('grades', function ($query) use ($gradeId) {
                $query->where('grades.id', $gradeId);
            });
        }
    }
    protected function filterBySubject()
    {
        if ($this->request->filled('subject')) {
            $subjectId = $this->request->input('subject');
            $this->builder->whereHas('subjects', function ($query) use ($subjectId) {
                $query->where('subjects.id', $subjectId);
            });
        }
    }

    // 並び順を適用
    protected function applySort()
    {
        $sort = $this->request->get('sort', self::DEFAULT_SORT);

        // 選択肢に存在しない値の場合はデフォルトを使用
        if (!array_key_exists($sort, self::SORT_OPTIONS)) {
            $sort = self::DEFAULT_SORT;
        }

        switch ($sort) {
            case 'price_asc':
                $this->builder->orderBy($this->getUsagePriceColumn(), 'asc');
                break;
            case 'price_desc':
                $this->builder->orderBy($this->getUsagePriceColumn(), 'desc');
                break;
            case 'created_at_asc':
                $this->builder->orderBy('created_at', 'asc');
                break;
            case 'created_at_desc':
                $this->builder->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $this->builder->withCount('orders')
                    ->orderBy('orders_count', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'rating':
                $this->builder->orderByDesc('rating_average')
                    ->orderBy('created_at', 'desc');
                break;
            case 'recommend':
            default:
                $this->builder->orderByRaw('display_order IS NULL, display_order ASC')
                    ->orderBy('created_at', 'desc');
                break;
        }
    }
    
    // 表示件数を取得（バリデーション付き）
    public function getPerPage()
    {
        $perPage = $this->request->get('per_page', self::DEFAULT_PER_PAGE);

        // 選択肢に存在しない値の場合はデフォルトを使用
        if (!array_key_exists($perPage, self::PER_PAGE_OPTIONS)) {
            return self::DEFAULT_PER_PAGE;
        }

        return (int) $perPage;
    }

    // ビューに渡すデータをまとめて取得
    public function getViewData()
    {
        return [
            'per_page' => self::PER_PAGE_OPTIONS,
            'sort' => self::SORT_OPTIONS,
            'usage' => self::USAGE_OPTIONS,
            'grade' => $this->grades,
            'subject' => $this->subjects,
        ];
    }

    protected function getUsagePriceColumn(): string
    {
        $usage = $this->request->input('usage', self::DEFAULT_USAGE);

        if ($usage === '2') {
            return 'price_for_school';
        }

        if ($usage === '3') {
            return 'price_for_commercial';
        }

        return 'price_for_personal';
    }
}
