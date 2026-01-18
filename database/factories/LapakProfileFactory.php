<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LapakProfile>
 */
class LapakProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lokasiCimanglid = ['Gg. Purnama', 'Sukamantri', 'Jl. Puspa', 'Tamansari', 'Pasir Eurih', 'Kavling Cimanglid'];

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company() . " Shop",
            'whatsapp_number' => '628' . $this->faker->numerify('##########'),
            'telegram_username' => $this->faker->userName(),
            'address_raw' => "Desa " . $this->faker->randomElement($lokasiCimanglid) . ", Ciapus, Bogor",
            'latitude' => -6.650000, // Koordinat sekitar Ciapus/Cimanglid
            'longitude' => 106.770000,
        ];
    }
}
