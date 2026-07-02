<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 消費税率を取得（デフォルト10%）
        $taxRate = 0.10;
        
        // 利用用途をランダムに選択（1:個人利用, 2:学校利用, 3:商用利用）
        $usage = rand(1, 3);
        
        // 商品をランダムに取得（Seederで上書きされる可能性がある）
        $product = Product::inRandomOrder()->first();
        $productName = $product?->product_name ?? $this->faker->word(20);
        
        // 利用用途に応じた価格を設定
        $basePrice = match($usage) {
            1 => $product?->price_for_personal ?? rand(500, 5000),
            2 => $product?->price_for_school ?? rand(500, 5000),
            3 => $product?->price_for_commercial ?? rand(500, 5000),
        };
        
        $quantity = rand(1, 5);
        $price = $basePrice * $quantity;
        $taxAmount = (int)($price * $taxRate);
        $totalAmount = $price + $taxAmount;
        
        // 支払い方法をランダムに選択
        $paymentMethods = ['credit_card', 'bank_transfer', 'paypal', 'points'];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        // ポイント支払い額（0-50%の範囲）
        $pointsPaid = rand(0, (int)($totalAmount * 0.5));
        $amountPaid = $totalAmount - $pointsPaid;
        
        // 取引手数料（支払い方法によって変動）
        $transactionFee = match($paymentMethod) {
            'credit_card' => (int)($amountPaid * 0.035), // 3.5%
            'paypal' => (int)($amountPaid * 0.029), // 2.9%
            default => 0,
        };
        
        // 注文日時（過去3ヶ月間のランダムな日時）
        $orderedAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
        
        // キャンセル日時（5%の確率でキャンセル）
        $canceledAt = null;
        if (rand(1, 100) <= 5) {
            $canceledAt = $orderedAt->copy()->addDays(rand(1, 7));
        }
        
        // ライセンス情報（商用利用の場合のみ、80%の確率で設定）
        $licence = null;
        if (rand(1, 10) <= 8) {
            $licences = [
                '単一ライセンス',
                '複数ライセンス（最大10ユーザー）',
                '企業ライセンス（無制限）',
                '年間ライセンス',
                '永続ライセンス'
            ];
            $licence = $licences[array_rand($licences)];
        }

        // 状態
        $status = 'pending';
        if (rand(1, 10) <= 8) {
            $status = 'completed';
        }
        
        // 備考（80%の確率で追加）
        $remark = null;
        if (rand(1, 10) <= 8) {
            $remarks = [
                '急ぎでお願いします',
                '学校の授業で使用します',
                '研修用として使用予定',
                '教材として活用します',
                '営業資料として使用します'
            ];
            $remark = $remarks[array_rand($remarks)];
        }
        
        // IPアドレスを生成（IPv4、80%の確率で設定）
        $ipAddress = rand(1, 10) <= 8 ? long2ip(rand(0, 4294967295)) : null;
        
        return [
            'product_name' => $productName,
            'usage' => $usage,
            'licence' => $licence,
            'price' => $price,
            'quantity' => $quantity,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'points_paid' => $pointsPaid,
            'amount_paid' => $amountPaid,
            'transaction_fee' => $transactionFee,
            'ordered_at' => $orderedAt,
            'remark' => $remark,
            'token' => 'token_' . uniqid(),
            'status' => $status,
            'canceled_at' => $canceledAt,
            'payment_method' => $paymentMethod,
            'transaction_id' => 'TXN' . str_pad(rand(1, 999999), 10, '0', STR_PAD_LEFT),
            'ip_address' => $ipAddress,
            'created_at' => $orderedAt,
            'updated_at' => $canceledAt ?? $orderedAt,
        ];
    }
}
