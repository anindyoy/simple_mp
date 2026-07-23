<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\LapakProfile;
use App\Models\RandomProductHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RandomProductHistoryFactory extends Factory
{
    protected $model = RandomProductHistory::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'lapak_id' => LapakProfile::factory(),
            'user_id' => User::factory(),
        ];
    }
}