<?php

namespace App\Filters\Member\Buy;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Grade;
use App\Models\Subject;

class OrderFilter
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
        $this->request->merge([
            'usage' => $this->request->get('usage', self::DEFAULT_USAGE),
        ]);
    }

    // 表示件数の選択肢
    public const PER_PAGE_OPTIONS = [
        10 => '10件',
        25 => '25件',
        50 => '50件',
        'all' => 'すべて',
    ];
    public const DEFAULT_PER_PAGE = 10;

    // 利用方法の選択肢
    public const USAGE_OPTIONS = [
        ''  => 'すべて',
        '1' => '個人利用',
        '2' => '学校利用',
        '3' => '商用利用',
    ];
    public const DEFAULT_USAGE = '';

    // 並び順の選択肢
    public const SORT_OPTIONS = [
        'ordered_at_desc' => '新しい順',
        'ordered_at_asc' => '古い順',
    ];
    public const DEFAULT_SORT = 'ordered_at_desc';

    public function apply(Builder $builder)
    {
        $this->builder = $builder;

        $this->filterName();
        $this->filterShop();
        $this->filterOrderId();
        $this->filterUsage();
        $this->filterByGrade();
        $this->filterBySubject();
        $this->applySort();

        return $this->builder;
    }

    // 各条件ごとのメソッド
    protected function filterName()
    {
        if ($this->request->filled('product_name')) {
            $keyword = $this->request->input('product_name');
            $this->builder->whereHas('product', function ($query) use ($keyword) {
                $query->where('product_name', 'like', "%{$keyword}%");
            });
        }
    }

    protected function filterShop()
    {
        if ($this->request->filled('shop')) {
            $keyword = $this->request->input('shop');
            $this->builder->whereHas('product.shop', function ($query) use ($keyword) {
                $query->where('shop_name', 'like', "%{$keyword}%");
            });
        }
    }

    protected function filterOrderId()
    {
        if ($this->request->filled('order_id')) {
            $this->builder->where('order_number', $this->request->input('order_id'));
        }
    }
    protected function filterUsage()
    {
        if ($this->request->filled('usage')) {
            $this->builder->where('usage', $this->request->input('usage'));
        }
    }

    protected function filterByGrade()
    {
        if ($this->request->filled('grade')) {
            $gradeId = $this->request->input('grade');
            $this->builder->whereHas('product.grades', function ($query) use ($gradeId) {
                $query->where('grades.id', $gradeId);
            });
        }
    }
    protected function filterBySubject()
    {
        if ($this->request->filled('subject')) {
            $subjectId = $this->request->input('subject');
            $this->builder->whereHas('product.subjects', function ($query) use ($subjectId) {
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
            case 'ordered_at_asc':
                $this->builder->orderBy('ordered_at', 'asc');
                break;
            case 'ordered_at_desc':
            default:
                $this->builder->orderBy('ordered_at', 'desc');
                break;
        }
    }
    
    // 表示件数を取得（バリデーション付き）
    public function getPerPage()
    {
        $perPage = $this->request->get('per_page', self::DEFAULT_PER_PAGE);

        // 選択肢に存在しない値の場合はデフォルトを使用
        if (!array_key_exists($perPage, self::PER_PAGE_OPTIONS) || $perPage === 'all') {
            return self::DEFAULT_PER_PAGE;
        }

        return (int) $perPage;
    }

    // ビューに渡すデータをまとめて取得
    // 選択肢がない場合は不要($filerで渡す)
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

}
