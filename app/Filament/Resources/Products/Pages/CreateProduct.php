<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

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
    }
}
