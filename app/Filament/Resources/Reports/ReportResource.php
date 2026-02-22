<?php

namespace App\Filament\Resources\Reports;

use BackedEnum;
use ValueError;
use App\Models\Report;
use App\Models\Product;
use Filament\Tables\Table;
use App\Models\LapakProfile;
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
            ->from('reports')

            // JOIN PRODUCT
            ->leftJoin('products', function ($join) {
                $join->on('products.id', '=', 'reports.reportable_id')
                    ->where('reports.reportable_type', Product::class);
            })

            // JOIN LAPAK (langsung)
            ->leftJoin('lapak_profiles as direct_lapak', function ($join) {
                $join->on('direct_lapak.id', '=', 'reports.reportable_id')
                    ->where('reports.reportable_type', LapakProfile::class);
            })

            // JOIN LAPAK dari PRODUCT
            ->leftJoin('lapak_profiles as product_lapak', function ($join) {
                $join->on('product_lapak.id', '=', 'products.lapak_id');
            })

            // JOIN USER (pemilik lapak)
            ->leftJoin('users as direct_owner', function ($join) {
                $join->on('direct_owner.id', '=', 'direct_lapak.user_id');
            })

            ->leftJoin('users as product_owner', function ($join) {
                $join->on('product_owner.id', '=', 'product_lapak.user_id');
            })

            ->select([
                'reports.reportable_type',
                'reports.reportable_id',

                DB::raw('COUNT(reports.id) as total_reports'),
                DB::raw('MAX(reports.created_at) as last_reported_at'),

                DB::raw('MAX(products.title) as product_title'),
                DB::raw('MAX(direct_lapak.name) as lapak_name'),

                DB::raw('MAX(product_lapak.name) as product_lapak_name'),

                DB::raw('MAX(direct_owner.name) as direct_owner_name'),
                DB::raw('MAX(product_owner.name) as product_owner_name'),

                DB::raw('MAX(products.is_active) as product_status'),
                DB::raw('MAX(direct_lapak.is_active) as lapak_status'),
            ])

            ->groupBy(
                'reports.reportable_type',
                'reports.reportable_id'
            );
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
