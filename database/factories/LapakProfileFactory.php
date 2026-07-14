<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
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
        $prefixLapak = ['Warung', 'Lapak', 'Toko', 'Kios'];
        $honorifik = ['Bu', 'Pak', 'Mbak', 'Mas', null];
        $externalLinkTemplates = [
            ['label' => 'Tokopedia', 'link' => 'https://www.tokopedia.com/' . Str::slug($this->faker->userName())],
            ['label' => 'Shopee', 'link' => 'https://shopee.co.id/' . Str::slug($this->faker->userName())],
            ['label' => 'Instagram', 'link' => 'https://instagram.com/' . Str::slug($this->faker->userName())],
            ['label' => 'Website', 'link' => $this->faker->url()],
        ];

        $externalLinks = collect($this->faker->randomElements(
            $externalLinkTemplates,
            $this->faker->numberBetween(1, 3)
        ))
            ->map(fn(array $item): array => [
                'label' => $item['label'],
                'link' => $item['link'],
            ])
            ->values()
            ->all();

        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'name' => $name = trim(implode(' ', array_filter([
                $this->faker->randomElement($prefixLapak),
                $this->faker->randomElement($honorifik),
                $this->faker->firstName(),
            ]))),
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'is_active' => 1,
            'profile_image' => asset('img/default-lapak-image.png'),
            'whatsapp_number' => '628' . $this->faker->numerify('##########'),
            'telegram_username' => $this->faker->userName(),
            'external_links' => $externalLinks,
            'address_raw' => "Desa " . $this->faker->randomElement($lokasiCimanglid) . ", Ciapus, Bogor",
            'can_be_delivered' => $this->faker->boolean(70), // 70% kemungkinan bisa diantar
        ];
    }
}
