<?php

namespace Database\Factories;

use App\Models\ReviewReply;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewReplyFactory extends Factory
{
    protected $model = ReviewReply::class;

    public function definition(): array
    {
        // sender_type を割合で決定
        // 1:40% 2:40% 3:20%
        $rand = rand(1, 100);
        if ($rand <= 40) {
            $senderType = 1; // 販売者
        } elseif ($rand <= 80) {
            $senderType = 2; // レビュー投稿者
        } else {
            $senderType = 3; // 管理者
        }

        return [
            // review_id と user_id は Seeder 側で上書きする
            'sender_type' => $senderType,
            'reply' => $this->faker->realText(100),
            'deleted_by_admin_at' => $this->faker->boolean(5)
                ? $this->faker->dateTime()
                : null,
            'deleted_by_sender_at' => $this->faker->boolean(5)
                ? $this->faker->dateTime()
                : null,
            'ip_address' => $this->faker->ipv4(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
