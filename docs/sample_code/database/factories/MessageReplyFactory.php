<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageReply>
 */
class MessageReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => \App\Models\Message::inRandomOrder()->first()?->id ?? 1,
            'user_id' => $this->faker->optional(0.8)->randomElement(\App\Models\User::pluck('id')->toArray()),
            'sender_type' => $this->faker->numberBetween(1, 3), // 1:販売者 2:メッセージ投稿者 3:サイト管理者
            'reply' => $this->faker->realText(250),
            'deleted_by_admin_at' => $this->faker->optional(0.1)->dateTimeBetween('-3 months', 'now'),
            'deleted_by_sender_at' => $this->faker->optional(0.1)->dateTimeBetween('-3 months', 'now'),
            'ip_address' => $this->faker->optional(0.8)->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
