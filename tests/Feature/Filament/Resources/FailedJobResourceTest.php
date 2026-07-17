<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\FailedJob;
use App\Filament\Resources\FailedJobResource;
use App\Filament\Resources\FailedJobResource\Pages\ListFailedJobs;

function makeFailedJob(array $attributes = []): FailedJob
{
    return FailedJob::create(array_merge([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['job' => 'App\\Jobs\\SomeJob']),
        'exception' => 'Exception: something went wrong',
        'failed_at' => now(),
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

it('restricts failed job resource access to admins', function () {
    $failedJob = makeFailedJob();

    $this->actingAs($this->user);

    expect(FailedJobResource::canViewAny())->toBeFalse();
    expect(FailedJobResource::canCreate())->toBeFalse();
    expect(FailedJobResource::canEdit($failedJob))->toBeFalse();
    expect(FailedJobResource::canDelete($failedJob))->toBeFalse();

    $this->actingAs($this->adminUser);

    expect(FailedJobResource::canViewAny())->toBeTrue();
    expect(FailedJobResource::canCreate())->toBeFalse();
    expect(FailedJobResource::canEdit($failedJob))->toBeFalse();
    expect(FailedJobResource::canDelete($failedJob))->toBeTrue();
});

// ==================== Table Rendering Tests ====================

it('renders failed job table with expected records', function () {
    $this->actingAs($this->adminUser);

    makeFailedJob();
    makeFailedJob();

    Livewire::test(ListFailedJobs::class)
        ->assertCanSeeTableRecords(FailedJob::all())
        ->assertCountTableRecords(2);
});

it('searches failed jobs by connection', function () {
    $this->actingAs($this->adminUser);

    $matching = makeFailedJob(['connection' => 'redis']);
    makeFailedJob(['connection' => 'database']);

    Livewire::test(ListFailedJobs::class)
        ->searchTable('redis')
        ->assertCanSeeTableRecords([$matching])
        ->assertCountTableRecords(1);
});

// ==================== Action Tests ====================

it('allows an admin to delete a failed job row', function () {
    $this->actingAs($this->adminUser);

    $failedJob = makeFailedJob();

    Livewire::test(ListFailedJobs::class)
        ->callTableAction('delete', $failedJob);

    expect(FailedJob::find($failedJob->id))->toBeNull();
});

it('allows an admin to bulk delete failed jobs', function () {
    $this->actingAs($this->adminUser);

    $failedJobOne = makeFailedJob();
    $failedJobTwo = makeFailedJob();

    Livewire::test(ListFailedJobs::class)
        ->callTableBulkAction('delete', [$failedJobOne, $failedJobTwo]);

    expect(FailedJob::count())->toBe(0);
});
