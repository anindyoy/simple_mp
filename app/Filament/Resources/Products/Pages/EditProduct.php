<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected array $uploadedImages = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['uploaded_images'] = $this->record->images()
            ->orderBy('id')
            ->pluck('image_url')
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->uploadedImages = array_values($data['uploaded_images'] ?? []);

        unset($data['uploaded_images']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->images()->delete();

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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
