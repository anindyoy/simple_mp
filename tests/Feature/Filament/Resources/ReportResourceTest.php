<?php

use App\Models\User;
use App\Models\Report;
use Livewire\Livewire;
use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use Illuminate\Support\Carbon;
use App\Models\ProductModeration;
use App\Services\ProductModerationService;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Pages\ViewReportDetails;

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->adminUser = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->category = Category::factory()->create();

    $this->lapakOwner = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->lapak = LapakProfile::factory()->create([
        'user_id' => $this->lapakOwner->id,
    ]);

    $this->product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $this->category->id,
        'condition' => null,
    ]);
});

// ==================== Access Control Tests ====================

it('restricts report resource access to admins', function () {
    $report = Report::factory()->forProduct($this->product)->create();

    $this->actingAs($this->user);

    expect(ReportResource::canViewAny())->toBeFalse();
    expect(ReportResource::shouldRegisterNavigation())->toBeFalse();
    expect(ReportResource::canCreate())->toBeFalse();
    expect(ReportResource::canEdit($report))->toBeFalse();
    expect(ReportResource::canDelete($report))->toBeFalse();

    $this->actingAs($this->adminUser);

    expect(ReportResource::canViewAny())->toBeTrue();
    expect(ReportResource::shouldRegisterNavigation())->toBeTrue();
    expect(ReportResource::canCreate())->toBeFalse();
    expect(ReportResource::canEdit($report))->toBeTrue();
    expect(ReportResource::canDelete($report))->toBeTrue();
});

it('generates the report details url with encoded target type', function () {
    $url = ReportResource::getUrl('details', [
        'type' => base64_encode(Product::class),
        'id' => $this->product->id,
    ]);

    expect($url)->toContain(base64_encode(Product::class));
    expect($url)->toContain((string) $this->product->id);
    expect($url)->toContain('/view');
});

// ==================== Query Aggregation Tests ====================

it('aggregates product and lapak reports in eloquent query', function () {
    $productReportOneAt = now()->subDays(2);
    $productReportTwoAt = now()->subHour();

    $productReportOne = Report::factory()->forProduct($this->product)->create([
        'reason' => 'spam',
        'status' => 'pending',
        'created_at' => $productReportOneAt,
        'updated_at' => $productReportOneAt,
    ]);

    $productReportTwo = Report::factory()->forProduct($this->product)->create([
        'reason' => 'penipuan',
        'status' => 'pending',
        'created_at' => $productReportTwoAt,
        'updated_at' => $productReportTwoAt,
    ]);

    $lapakReport = Report::factory()->forLapak($this->lapak)->create([
        'reason' => 'konten_tidak_pantas',
        'status' => 'pending',
    ]);

    $rows = ReportResource::getEloquentQuery()->get();

    expect($rows)->toHaveCount(2);

    $productRow = $rows->firstWhere('reportable_type', Product::class);
    $lapakRow = $rows->firstWhere('reportable_type', LapakProfile::class);

    expect($productRow->reportable_id)->toBe($this->product->id);
    expect($productRow->total_reports)->toBe(2);
    expect($productRow->product_title)->toBe($this->product->title);
    expect($productRow->product_lapak_name)->toBe($this->lapak->name);
    expect($productRow->product_owner_name)->toBe($this->lapakOwner->name);
    expect($productRow->lapak_name)->toBeNull();
    expect($productRow->direct_owner_name)->toBeNull();
    expect(Carbon::parse($productRow->last_reported_at)->toDateTimeString())->toBe($productReportTwoAt->toDateTimeString());

    expect($lapakRow->reportable_id)->toBe($this->lapak->id);
    expect($lapakRow->total_reports)->toBe(1);
    expect($lapakRow->lapak_name)->toBe($this->lapak->name);
    expect($lapakRow->direct_owner_name)->toBe($this->lapakOwner->name);
    expect($lapakRow->product_title)->toBeNull();
    expect($lapakRow->product_owner_name)->toBeNull();
    expect($lapakRow->product_lapak_name)->toBeNull();
});

it('filters aggregated rows by report type and minimum report count logic', function () {
    Report::factory()->forProduct($this->product)->create([
        'reason' => 'spam',
        'status' => 'pending',
    ]);

    Report::factory()->forProduct($this->product)->create([
        'reason' => 'penipuan',
        'status' => 'pending',
    ]);

    $otherLapakOwner = User::factory()->create();
    $otherLapak = LapakProfile::factory()->create([
        'user_id' => $otherLapakOwner->id,
    ]);

    Report::factory()->forLapak($otherLapak)->create([
        'reason' => 'spam',
        'status' => 'pending',
    ]);

    $productRows = ReportResource::getEloquentQuery()
        ->where('aggregated_reports.reportable_type', Product::class)
        ->get();

    $minimumReportRows = ReportResource::getEloquentQuery()
        ->where('aggregated_reports.total_reports', '>=', 2)
        ->get();

    expect($productRows)->toHaveCount(1);
    expect($productRows->first()->reportable_id)->toBe($this->product->id);

    expect($minimumReportRows)->toHaveCount(1);
    expect($minimumReportRows->first()->reportable_id)->toBe($this->product->id);
});

// ==================== Detail Page Mount Tests ====================

it('mounts product report details with product target and latest reports first', function () {
    $olderReport = Report::factory()->forProduct($this->product)->create([
        'reason' => 'spam',
        'status' => 'pending',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $newerReport = Report::factory()->forProduct($this->product)->create([
        'reason' => 'penipuan',
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $page = app(ViewReportDetails::class);
    $page->mount(base64_encode(Product::class), $this->product->id);

    expect($page->target->id)->toBe($this->product->id);
    expect($page->target->relationLoaded('lapak'))->toBeTrue();
    expect($page->target->lapak->relationLoaded('user'))->toBeTrue();
    expect($page->reports)->toHaveCount(2);
    expect($page->reports->first()->id)->toBe($newerReport->id);
    expect($page->reports->last()->id)->toBe($olderReport->id);
});

it('mounts lapak report details with lapak target', function () {
    $lapakReport = Report::factory()->forLapak($this->lapak)->create([
        'reason' => 'spam',
        'status' => 'pending',
    ]);

    $page = app(ViewReportDetails::class);
    $page->mount(base64_encode(LapakProfile::class), $this->lapak->id);

    expect($page->target->id)->toBe($this->lapak->id);
    expect($page->target->relationLoaded('user'))->toBeTrue();
    expect($page->reports)->toHaveCount(1);
    expect($page->reports->first()->id)->toBe($lapakReport->id);
});

// ==================== Deactivation Flow Tests ====================

it('deactivates reported product and marks pending reports as reviewed', function () {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $this->category->id,
        'condition' => null,
        'is_active' => true,
    ]);

    $olderReport = Report::factory()->forProduct($product)->create([
        'reason' => 'spam',
        'status' => 'pending',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $latestReport = Report::factory()->forProduct($product)->create([
        'reason' => 'penipuan',
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(ProductModerationService::class);
    $moderation = $service->deactivateTarget(
        $product,
        $this->adminUser,
        'Produk melanggar aturan',
        'Dinonaktifkan dari laporan pengguna',
        $latestReport,
    );

    expect($moderation)->toBeInstanceOf(ProductModeration::class);
    expect($moderation->type)->toBe(ProductModeration::TYPE_DEACTIVATION);
    expect($moderation->status)->toBe(ProductModeration::STATUS_APPROVED);
    expect($moderation->report_id)->toBe($latestReport->id);
    expect($product->refresh()->is_active)->toBeFalse();
    expect($olderReport->refresh()->status)->toBe('reviewed');
    expect($latestReport->refresh()->status)->toBe('reviewed');
});

it('renders report table with aggregated data correctly', function () {
    $this->actingAs($this->adminUser);

    Report::factory()->forProduct($this->product)->count(2)->create();

    $expected = ReportResource::getEloquentQuery()->get();

    Livewire::test(ListReports::class)
        ->assertCanSeeTableRecords($expected)
        ->assertCountTableRecords(1); // karena grouping
});

it('filters table by reportable type (product)', function () {
    $this->actingAs($this->adminUser);

    Report::factory()->forProduct($this->product)->count(2)->create();

    $lapak = LapakProfile::factory()->create();
    Report::factory()->forLapak($lapak)->create();

    $expected = ReportResource::getEloquentQuery()
        ->where('aggregated_reports.reportable_type', Product::class)
        ->get();

    Livewire::test(ListReports::class)
        ->filterTable('reportable_type', Product::class)
        ->assertCanSeeTableRecords($expected)
        ->assertCountTableRecords(1);
});

it('filters table by minimum report count', function () {
    $this->actingAs($this->adminUser);

    Report::factory()->forProduct($this->product)->count(2)->create();

    $lapak = LapakProfile::factory()->create();
    Report::factory()->forLapak($lapak)->create();

    Livewire::test(ListReports::class)
        ->filterTable('minimum_reports', [
            'min' => 2,
        ])
        ->assertCountTableRecords(1);
});

it('generates correct detail url from record data', function () {
    Report::factory()->forProduct($this->product)->count(2)->create();

    $record = ReportResource::getEloquentQuery()->first();

    $url = ReportResource::getUrl('details', [
        'type' => base64_encode($record->reportable_type),
        'id' => $record->reportable_id,
    ]);

    expect($url)
        ->toContain(base64_encode($record->reportable_type))
        ->toContain((string) $record->reportable_id)
        ->toContain('/view');
});
