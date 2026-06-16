<?php

namespace App\Traits;

use App\Models\Product;
use Illuminate\Support\Str;

trait AttachesProductImages
{
    protected function attachRandomImages(Product $product): void
    {
        $seedDir = storage_path('app/seed-samples');

        if (! is_dir($seedDir)) {
            return;
        }

        $categoryName = mb_strtolower($product->category?->category_name ?? '');
        $categoryDir  = $seedDir . DIRECTORY_SEPARATOR . $categoryName;

        $isFromCategoryDir = is_dir($categoryDir);
        $sourceDir = $isFromCategoryDir ? $categoryDir : $seedDir;

        $files = $this->scanImageFiles($sourceDir);

        if (empty($files)) {
            return;
        }

        foreach (range(1, rand(1, 3)) as $_) {
            $this->attachImage($product, $files);
        }
    }

    protected function scanImageFiles(string $dir): array
    {
        return collect(scandir($dir))
            ->reject(fn($f) => in_array($f, ['.', '..']) || is_dir($dir . DIRECTORY_SEPARATOR . $f))
            ->filter(fn($f) => ! str_ends_with($f, '.json'))
            ->map(fn($f) => $dir . DIRECTORY_SEPARATOR . $f)
            ->values()
            ->toArray();
    }

    protected function attachImage(Product $product, array $files): void
    {
        $fullPath = $files[array_rand($files)];
        $tempPath = null;

        try {
            $ext      = pathinfo($fullPath, PATHINFO_EXTENSION);
            $tempPath = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'product-seed-' . bin2hex(random_bytes(8))
                . ($ext !== '' ? '.' . $ext : '');

            if (! copy($fullPath, $tempPath)) {
                return;
            }

            $media = $product
                ->addMedia($tempPath)
                ->toMediaCollection('products');

            if (blank($media->uuid)) {
                $media->forceFill(['uuid' => (string) Str::uuid()])->save();
            }
        } catch (\Throwable $e) {
            logger()->error('Gagal attach gambar produk', [
                'message' => $e->getMessage(),
            ]);

            if ($tempPath && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}