<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

class ConsumptionTax extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'tax_rate',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'tax_rate' => 'decimal:4',
        ];
    }

    /**
     * 指定日時点で有効な消費税率を取得する（ショップは1つのみなので区分なし）。
     */
    public static function forDate(CarbonInterface|string|null $date = null): ?self
    {
        $targetDate = $date === null
            ? Carbon::now()
            : Carbon::parse($date);

        return self::query()
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * 現在有効な消費税率。未設定なら例外。
     */
    public static function current(CarbonInterface|string|null $date = null): self
    {
        $tax = self::forDate($date);

        if ($tax === null) {
            throw new RuntimeException('有効な消費税率が consumption_taxes に登録されていません。');
        }

        return $tax;
    }

    /**
     * 税抜金額から外税の消費税額を算出（切り捨て）。
     *
     * tax_rate 0.10 → floor(amount × 10 / 100)
     */
    public function taxFromExclusive(int $exclusiveYen): int
    {
        $rateBasis = (int) round((float) $this->tax_rate * 10000);

        if ($rateBasis <= 0 || $exclusiveYen <= 0) {
            return 0;
        }

        return (int) floor($exclusiveYen * $rateBasis / 10000);
    }

    /**
     * 税抜金額から税込金額を算出（外税・切り捨て）。
     */
    public function inclusiveFromExclusive(int $exclusiveYen): int
    {
        return $exclusiveYen + $this->taxFromExclusive($exclusiveYen);
    }

    /**
     * 税込金額から内税の消費税額を算出（切り捨て）。
     *
     * カラーミー移行注文（税込スナップショット）のフォールバック用。
     * tax_rate 0.10 → floor(amount × 10 / 110)
     */
    public function extractFromInclusive(int $inclusiveYen): int
    {
        $rateBasis = (int) round((float) $this->tax_rate * 10000);

        if ($rateBasis <= 0) {
            return 0;
        }

        return (int) floor($inclusiveYen * $rateBasis / (10000 + $rateBasis));
    }

    /**
     * 表示用の百分率（例: 0.10 → 10）。
     */
    public function percent(): float
    {
        return round((float) $this->tax_rate * 100, 4);
    }

    /**
     * 表示用ラベル（例: "10%"）。
     */
    public function percentLabel(): string
    {
        $percent = $this->percent();
        $formatted = rtrim(rtrim(number_format($percent, 4, '.', ''), '0'), '.');

        return $formatted.'%';
    }
}
