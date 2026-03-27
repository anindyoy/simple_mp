<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenPurchase>
 */
class TokenPurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(5, 10);
        $tokenPrice = 2000;
        $totalPrice = $quantity * $tokenPrice;
        $status = $this->faker->randomElement(['pending', 'confirmed', 'cancelled']);
        $created = $this->faker->dateTimeBetween('-30 days', 'now');

        $confirmedUntil = (clone $created)->modify('+2 days');
        if ($confirmedUntil > now()) {
            $confirmedUntil = now();
        }

        $confirmedAt = $status === 'confirmed'
            ? $this->faker->dateTimeBetween($created, $confirmedUntil)
            : null;

        return [
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'status' => $status,
            'payment_method' => 'bank_transfer',
            'bank_account' => $this->faker->randomElement($this->bankAccountOptions()),
            'proof_of_payment' => null,
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => $created,
            'confirmed_at' => $confirmedAt,
        ];
    }

    /**
     * Bank account options used by token purchase factory.
     *
     * @return array<int, string>
     */
    protected function bankAccountOptions(): array
    {
        return [
            '1234567890',
            '9876543210',
            '1122334455',
        ];
    }

    /**
     * Indicate that the purchase is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the purchase is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'confirmed_at' => null,
        ]);
    }

    /**
     * Indicate that the purchase is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
            'confirmed_at' => null,
        ]);
    }
}
