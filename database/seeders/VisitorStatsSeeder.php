<?php

namespace Database\Seeders;

use App\Models\VisitorStats;
use Illuminate\Database\Seeder;

class VisitorStatsSeeder extends Seeder
{
    public function run(): void
    {
        if (VisitorStats::count() > 0) {
            return;
        }

        $records = collect(range(1, 30))->map(fn (int $i) => [
            'date' => now()->subDays($i)->format('Y-m-d'),
            'visitor_count' => fake()->numberBetween(1, 200),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        VisitorStats::insert($records->all());
    }
}