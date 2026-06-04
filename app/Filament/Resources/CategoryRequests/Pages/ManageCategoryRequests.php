<?php

namespace App\Filament\Resources\CategoryRequests\Pages;

use App\Filament\Resources\CategoryRequests\CategoryRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCategoryRequests extends ManageRecords
{
    protected static string $resource = CategoryRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
