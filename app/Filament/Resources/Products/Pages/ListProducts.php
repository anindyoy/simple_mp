<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Widgets\PushCountdownWidget;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->hidden(auth()->user()->is_admin),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PushCountdownWidget::class,
        ];
    }
}
