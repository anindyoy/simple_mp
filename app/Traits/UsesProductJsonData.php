<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UsesProductJsonData
{
    /** @var array<string, array<string>> */
    private array $productNames = [];

    /** @var array<string, array<string>> */
    private array $productSifat = [];

    /** @var array<string, array{min: int, max: int}> */
    private array $productPrices = [];

    private function loadJsonData(): void
    {
        $path = storage_path('app/seed-samples/item_produk.json');

        if (!file_exists($path)) {
            return;
        }

        $data = json_decode(file_get_contents($path), true) ?? [];

        foreach ($data as $kategori => $attributes) {
            $key = mb_strtolower($kategori);

            $this->productNames[$key] = $attributes['nama'] ?? [];
            $this->productSifat[$key] = $attributes['sifat'] ?? [];
            $this->productPrices[$key] = $attributes['harga'] ?? ['min' => 10_000, 'max' => 2_000_000];
        }
    }

    /**
     * Generate title dan slug dari data JSON berdasarkan nama kategori.
     *
     * @return array{0: string, 1: string} [title, slug]
     */
    private function makeProductTitle(string $categoryName): array
    {
        $key = mb_strtolower($categoryName);

        $names = $this->productNames[$key] ?? [];
        $sifat = $this->productSifat[$key] ?? [];

        if (!empty($names) && !empty($sifat)) {
            $nama  = $names[array_rand($names)];
            $sif   = $sifat[array_rand($sifat)];
            $title = "{$nama} {$sif}";
        } elseif (!empty($names)) {
            $title = $names[array_rand($names)];
        } else {
            $title = fake()->words(3, true);
        }

        $slug = Str::slug($title) . '-' . rand(100, 999);

        return [$title, $slug];
    }

    /**
     * Generate harga berdasarkan range dari JSON untuk kategori tertentu.
     */
    private function generatePrice(string $categoryName): int
    {
        $range = $this->productPrices[mb_strtolower($categoryName)]
            ?? ['min' => 10_000, 'max' => 2_000_000];

        return (int) round(fake()->numberBetween($range['min'], $range['max']), -3);
    }
}