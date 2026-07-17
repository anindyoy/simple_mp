<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\Job;
use App\Filament\Resources\JobResource;
use App\Filament\Resources\JobResource\Pages\ListJobs;

function makeJob(array $attributes = []): Job
{
    return Job::create(array_merge([
        'queue' => 'default',
        'payload' => ['displayName' => 'App\\Jobs\\SomeJob'],
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
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

it('restricts job resource access to admins', function () {
    $job = makeJob();

    $this->actingAs($this->user);

    expect(JobResource::canViewAny())->toBeFalse();
    expect(JobResource::canCreate())->toBeFalse();
    expect(JobResource::canEdit($job))->toBeFalse();
    expect(JobResource::canDelete($job))->toBeFalse();

    $this->actingAs($this->adminUser);

    expect(JobResource::canViewAny())->toBeTrue();
    expect(JobResource::canCreate())->toBeFalse();
    expect(JobResource::canEdit($job))->toBeFalse();
    expect(JobResource::canDelete($job))->toBeTrue();
});

// ==================== Table Rendering Tests ====================

it('renders job table with expected records', function () {
    $this->actingAs($this->adminUser);

    makeJob();
    makeJob();

    Livewire::test(ListJobs::class)
        ->assertCanSeeTableRecords(Job::all())
        ->assertCountTableRecords(2);
});

it('searches jobs by queue', function () {
    $this->actingAs($this->adminUser);

    $matching = makeJob(['queue' => 'emails']);
    makeJob(['queue' => 'default']);

    Livewire::test(ListJobs::class)
        ->searchTable('emails')
        ->assertCanSeeTableRecords([$matching])
        ->assertCountTableRecords(1);
});

it('exposes the job display name from its payload', function () {
    $job = makeJob(['payload' => ['displayName' => 'App\\Jobs\\SendNotification']]);

    expect($job->display_name)->toBe('App\\Jobs\\SendNotification');
});

// ==================== Action Tests ====================

it('allows an admin to delete a job row', function () {
    $this->actingAs($this->adminUser);

    $job = makeJob();

    Livewire::test(ListJobs::class)
        ->callTableAction('delete', $job);

    expect(Job::find($job->id))->toBeNull();
});

it('allows an admin to bulk delete jobs', function () {
    $this->actingAs($this->adminUser);

    $jobOne = makeJob();
    $jobTwo = makeJob();

    Livewire::test(ListJobs::class)
        ->callTableBulkAction('delete', [$jobOne, $jobTwo]);

    expect(Job::count())->toBe(0);
});
