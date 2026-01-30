konteks

-- simple_mp.lapak_profiles definition

CREATE TABLE `lapak_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `telegram_username` varchar(50) DEFAULT NULL,
  `address_raw` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lapak_profiles_user_id_foreign` (`user_id`),
  CONSTRAINT `lapak_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
