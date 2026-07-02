<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Information>
 */
class InformationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('ja_JP');

        // Base date around now within ±90 days
        $baseDate = Carbon::now()->clone()->addDays($faker->numberBetween(-90, 90));

        // Decide nullability (~30%) independently for start/end
        $isStartNull = $faker->boolean(30);
        $isEndNull = $faker->boolean(30);

        $startAt = null;
        $endAt = null;

        if (!$isStartNull) {
            // Start time offset within ±7 days around base
            $startAt = $baseDate->clone()->addDays($faker->numberBetween(-7, 3))->addMinutes($faker->numberBetween(0, 1440));
        }

        if (!$isEndNull) {
            if ($startAt) {
                // Ensure end >= start when both exist
                $endAt = $startAt->clone()->addDays($faker->numberBetween(0, 14))->addMinutes($faker->numberBetween(0, 1440));
            } else {
                // End around base if start is null
                $endAt = $baseDate->clone()->addDays($faker->numberBetween(0, 21))->addMinutes($faker->numberBetween(0, 1440));
            }
        }

        return [
            'title' => $faker->realText($faker->numberBetween(12, 28)),
            'body' => $faker->realText($faker->numberBetween(120, 400)),
            'important' => $faker->boolean(20) ? 1 : 0,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ];
    }
}
