<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Support\Facades\Storage;
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

        Storage::disk('public')->makeDirectory('lapak-profiles');
        $profileImage = $this->faker->image(
            storage_path('app/public/lapak-profiles'),
            640,
            480,
            null,
            false
        );
        if (! $profileImage) {
            $profileImage = Str::uuid() . '.png';
            $fullPath = storage_path('app/public/lapak-profiles/' . $profileImage);

            if (function_exists('imagecreatetruecolor') && function_exists('imagepng')) {
                $image = imagecreatetruecolor(640, 480);
                $bg = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));
                imagefilledrectangle($image, 0, 0, 640, 480, $bg);
                imagepng($image, $fullPath);
                imagedestroy($image);
            } else {
                Storage::disk('public')->put('lapak-profiles/' . $profileImage, '');
            }
        }

        return [
            'user_id' => User::factory(),
            'name' => $name = $this->faker->company() . " Shop",
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'profile_image' => 'lapak-profiles/' . $profileImage,
            'whatsapp_number' => '628' . $this->faker->numerify('##########'),
            'telegram_username' => $this->faker->userName(),
            'address_raw' => "Desa " . $this->faker->randomElement($lokasiCimanglid) . ", Ciapus, Bogor",
        ];
    }
}
