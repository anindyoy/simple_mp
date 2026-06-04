<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'category_name' => $this->faker->words(2, true),
            'reason'        => $this->faker->optional()->sentence(),
            'status'        => 'pending',
            'admin_note'    => null,
            'approved_category_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'     => 'rejected',
            'admin_note' => $this->faker->sentence(),
        ]);
    }
}
