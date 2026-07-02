<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ConsumptionTax extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'tax_rate',
        'classification_id',
        'classification',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'tax_rate' => 'decimal:4',
        'classification_id' => 'integer',
    ];

    // リレーション

    // クエリスコープ

    // ビジネスメソッド

    /**
     * 指定日時点で有効な消費税区分の一覧を取得（ショップ選択用）。
     * 現在の税率期間のみを対象とし、過去・未来の税率は含めない。
     *
     * @return array<int, string> classification_id => classification の連想配列
     */
    public static function getClassificationsForSelect($date = null): array
    {
        $targetDate = $date ?? Carbon::now();

        return self::whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->orderBy('classification_id')
            ->get()
            ->unique('classification_id')
            ->mapWithKeys(fn (self $row) => [$row->classification_id => $row->classification])
            ->all();
    }

    public static function getByClassificationAndDate(int $classificationId, $date = null): ?self
    {
        $now = $date ?? Carbon::now();

        return self::where('classification_id', $classificationId)
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->first();
    }
    // 日本語訳
    public function attributes()
    {
        return [
            'start_date' => '開始日',
            'end_date' => '終了日',
            'tax_rate' => '消費税率',
            'classification_id' => '消費税区分ID',
            'classification' => '消費税区分',
        ];
    }

}
