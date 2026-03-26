<?php

namespace App\Filament\Resources\TokenPurchases\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TokenPurchases\TokenPurchaseResource;

class ListTokenPurchases extends ListRecords
{
    protected static string $resource = TokenPurchaseResource::class;
}
