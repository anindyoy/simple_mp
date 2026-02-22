<?php

namespace App\Filament\Resources\Reports\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Reports\ReportResource;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    public function getTableRecordKey(Model | array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['key'] ?? $record['reportable_type'] . '-' . $record['reportable_id']);
        }

        return $record->reportable_type . '-' . $record->reportable_id;
    }
}
