<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Report;
use App\Models\Product;
use App\Models\ProductModeration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductModeration>
 */
class ProductModerationFactory extends Factory
{
    protected $model = ProductModeration::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'lapak_profile_id' => null,

            'report_id' => $this->faker->boolean(40)
                ? Report::factory()
                : null,

            'requested_by_user_id' => User::factory(),

            'reviewed_by_user_id' => $this->faker->boolean(70)
                ? User::factory()
                : null,

            'type' => $this->faker->randomElement([
                ProductModeration::TYPE_DEACTIVATION,
                ProductModeration::TYPE_REACTIVATION,
            ]),

            'status' => $this->faker->randomElement([
                ProductModeration::STATUS_PENDING,
                ProductModeration::STATUS_APPROVED,
                ProductModeration::STATUS_REJECTED,
            ]),

            'reason' => $this->faker->optional()->sentence(),

            'description' => $this->faker->optional()->paragraph(),

            'reviewed_at' => $this->faker->boolean(70)
                ? $this->faker->dateTimeBetween('-1 month', 'now')
                : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ProductModeration::STATUS_PENDING,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ProductModeration::STATUS_APPROVED,
            'reviewed_by_user_id' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ProductModeration::STATUS_REJECTED,
            'reviewed_by_user_id' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function deactivation(): static
    {
        return $this->state(fn () => [
            'type' => ProductModeration::TYPE_DEACTIVATION,
        ]);
    }

    public function reactivation(): static
    {
        return $this->state(fn () => [
            'type' => ProductModeration::TYPE_REACTIVATION,
        ]);
    }
}