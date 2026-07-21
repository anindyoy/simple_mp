<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductView>
 */
class ProductViewFactory extends Factory
{
    protected $model = ProductView::class;

    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-48 hours', 'now');

        return [
            'product_id' => Product::factory(),
            'ip_address' => $this->faker->ipv4(),
            'expires_at' => (clone $createdAt)->modify('+6 hours'),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
