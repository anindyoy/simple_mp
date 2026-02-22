<?php

namespace App\Filament\Resources\Reports;

use BackedEnum;
use ValueError;
use App\Models\Report;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Reports\Pages\EditReport;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Pages\CreateReport;
use App\Filament\Resources\Reports\Schemas\ReportForm;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use App\Filament\Resources\Reports\Pages\ViewReportDetails;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    // protected static ?string $navigationGroup = 'Moderasi';
    protected static ?string $navigationLabel = 'Laporan';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function canCreate(): bool
    {
        return false; // Admin tidak perlu create manual
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->is_admin;
    }

    public static function form(Schema $schema): Schema
    {
        return ReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'reportable_type',
                'reportable_id',
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('MAX(created_at) as last_reported_at'),
            ])
            ->groupBy('reportable_type', 'reportable_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'edit' => EditReport::route('/{record}/edit'),
            'details' => ViewReportDetails::route('/{type}/{id}/view'),
        ];
    }
}
