<?php

use App\Models\User;
use App\Models\FailedJob;
use Livewire\Livewire;
use App\Filament\Resources\FailedJobResource;
use App\Filament\Resources\FailedJobResource\Pages\ListFailedJobs;

function makeListedFailedJob(array $attributes = []): FailedJob
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
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

describe('authorization', function () {
    test('admin dapat mengakses halaman failed jobs', function () {
        $this->actingAs($this->admin);

        $this->get(ListFailedJobs::getUrl())
            ->assertOk();
    });

    test('non admin diarahkan dari halaman failed jobs', function () {
        $this->actingAs($this->user);

        $this->get(ListFailedJobs::getUrl())
            ->assertRedirect();
    });
});

it('uses FailedJobResource as its resource', function () {
    $page = new ListFailedJobs();

    $reflection = new ReflectionClass($page);
    $property = $reflection->getProperty('resource');
    $property->setAccessible(true);

    expect($property->getValue($page))->toBe(FailedJobResource::class);
});

it('has no header actions since creating failed jobs is disabled', function () {
    $this->actingAs($this->admin);

    $page = new ListFailedJobs();

    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderActions');
    $method->setAccessible(true);

    expect($method->invoke($page))->toBe([]);
});

it('lists failed jobs sorted by latest failure first', function () {
    $this->actingAs($this->admin);

    $older = makeListedFailedJob(['failed_at' => now()->subDay()]);
    $newer = makeListedFailedJob(['failed_at' => now()]);

    Livewire::test(ListFailedJobs::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('renders empty state when there are no failed jobs', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListFailedJobs::class)
        ->assertCountTableRecords(0)
        ->assertSuccessful();
});
