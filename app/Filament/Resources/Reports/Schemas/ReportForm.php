<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),

                Textarea::make('description')
                    ->label('Keterangan Reporter')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}
