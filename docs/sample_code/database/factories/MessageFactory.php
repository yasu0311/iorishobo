<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::inRandomOrder()->first()?->id ?? 1,
            'user_id' => \App\Models\User::inRandomOrder()->first()?->id ?? 1,
            'title' => $this->faker->text(20),
            'public_sender' => $this->faker->numberBetween(0, 1),
            'public_shop' => $this->faker->numberBetween(0, 1),
            'message' => $this->faker->realText(300),
            'deleted_by_sender_at' => $this->faker->optional(0.1)->dateTimeBetween('-6 months', 'now'),
            'deleted_by_admin_at' => $this->faker->optional(0.1)->dateTimeBetween('-6 months', 'now'),
            'ip_address' => $this->faker->optional(0.8)->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
