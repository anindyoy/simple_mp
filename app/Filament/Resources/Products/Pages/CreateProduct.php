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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
