<?php

namespace App\Filament\Resources\TutorialPages\Pages;

use App\Filament\Resources\TutorialPages\TutorialPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTutorialPage extends EditRecord
{
    protected static string $resource = TutorialPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
