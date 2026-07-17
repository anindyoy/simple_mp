<?php

namespace App\Filament\Resources\JobBatchResource\Pages;

use App\Filament\Resources\JobBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListJobBatches extends ListRecords
{
    protected static string $resource = JobBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
