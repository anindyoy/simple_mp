<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\JobBatch;
use App\Filament\Resources\JobBatchResource;
use App\Filament\Resources\JobBatchResource\Pages\ListJobBatches;

function makeJobBatch(array $attributes = []): JobBatch
{
    return JobBatch::create(array_merge([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Import Produk',
        'total_jobs' => 10,
        'pending_jobs' => 5,
        'failed_jobs' => 0,
        'failed_job_ids' => '[]',
        'options' => null,
        'cancelled_at' => null,
        'created_at' => now()->timestamp,
        'finished_at' => null,
    ], $attributes));
}

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->adminUser = User::factory()->create([
        'is_admin' => true,
    ]);
});

// ==================== Access Control Tests ====================

it('restricts job batch resource access to admins', function () {
    $jobBatch = makeJobBatch();

    $this->actingAs($this->user);

    expect(JobBatchResource::canViewAny())->toBeFalse();
    expect(JobBatchResource::canCreate())->toBeFalse();
    expect(JobBatchResource::canEdit($jobBatch))->toBeFalse();
    expect(JobBatchResource::canDelete($jobBatch))->toBeFalse();

    $this->actingAs($this->adminUser);

    expect(JobBatchResource::canViewAny())->toBeTrue();
    expect(JobBatchResource::canCreate())->toBeFalse();
    expect(JobBatchResource::canEdit($jobBatch))->toBeFalse();
    expect(JobBatchResource::canDelete($jobBatch))->toBeTrue();
});

// ==================== Table Rendering Tests ====================

it('renders job batch table with expected records', function () {
    $this->actingAs($this->adminUser);

    makeJobBatch();
    makeJobBatch();

    Livewire::test(ListJobBatches::class)
        ->assertCanSeeTableRecords(JobBatch::all())
        ->assertCountTableRecords(2);
});

it('searches job batches by name', function () {
    $this->actingAs($this->adminUser);

    $matching = makeJobBatch(['name' => 'Kirim Notifikasi']);
    makeJobBatch(['name' => 'Import Produk']);

    Livewire::test(ListJobBatches::class)
        ->searchTable('Kirim Notifikasi')
        ->assertCanSeeTableRecords([$matching])
        ->assertCountTableRecords(1);
});

// ==================== Action Tests ====================

it('allows an admin to delete a job batch row', function () {
    $this->actingAs($this->adminUser);

    $jobBatch = makeJobBatch();

    Livewire::test(ListJobBatches::class)
        ->callTableAction('delete', $jobBatch);

    expect(JobBatch::find($jobBatch->id))->toBeNull();
});

it('allows an admin to bulk delete job batches', function () {
    $this->actingAs($this->adminUser);

    $jobBatchOne = makeJobBatch();
    $jobBatchTwo = makeJobBatch();

    Livewire::test(ListJobBatches::class)
        ->callTableBulkAction('delete', [$jobBatchOne, $jobBatchTwo]);

    expect(JobBatch::count())->toBe(0);
});
