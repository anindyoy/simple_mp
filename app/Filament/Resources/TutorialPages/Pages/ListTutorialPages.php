<?php

namespace App\Filament\Resources\TutorialPages\Pages;

use App\Filament\Resources\TutorialPages\TutorialPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTutorialPages extends ListRecords
{
    protected static string $resource = TutorialPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
