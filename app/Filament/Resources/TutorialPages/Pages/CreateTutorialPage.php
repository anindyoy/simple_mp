<?php

namespace App\Filament\Resources\TutorialPages\Pages;

use App\Filament\Resources\TutorialPages\TutorialPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTutorialPage extends CreateRecord
{
    protected static string $resource = TutorialPageResource::class;
}
