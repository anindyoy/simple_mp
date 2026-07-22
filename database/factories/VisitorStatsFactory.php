<?php

namespace Database\Factories;

use App\Models\VisitorStats;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisitorStats>
 */
class VisitorStatsFactory extends Factory
{
    protected $model = VisitorStats::class;

    public function definition(): array
    {
        $date = now()->subDays($this->faker->numberBetween(1, 30))->format('Y-m-d');

        return [
            'date' => $date,
            'visitor_count' => $this->faker->numberBetween(1, 200),
        ];
    }
}