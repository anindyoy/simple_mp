<?php

namespace App\Filament\Resources\Products\Pages;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Products\ProductResource;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $uploadedImages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->uploadedImages = array_values($data['uploaded_images'] ?? []);

        unset($data['uploaded_images']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->record->images()->createMany(
            collect($this->uploadedImages)
                ->map(fn(string $imageUrl): array => [
                    'image_url' => $imageUrl,
                    'is_primary' => false,
                ])
                ->all()
        );

        $this->optimizeUploadedImages();
    }

    protected function optimizeUploadedImages(): void
    {
        foreach ($this->uploadedImages as $imageUrl) {
            if (! Storage::disk('public')->exists($imageUrl)) {
                continue;
            }

            try {
                ImageOptimizer::optimize(Storage::disk('public')->path($imageUrl));
            } catch (\Throwable $exception) {
                Log::warning('Image optimization skipped.', [
                    'path' => $imageUrl,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
