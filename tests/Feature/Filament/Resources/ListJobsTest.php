<?php

use App\Models\User;
use App\Models\Job;
use Livewire\Livewire;
use App\Filament\Resources\JobResource;
use App\Filament\Resources\JobResource\Pages\ListJobs;

function makeListedJob(array $attributes = []): Job
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
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

describe('authorization', function () {
    test('admin dapat mengakses halaman jobs', function () {
        $this->actingAs($this->admin);

        $this->get(ListJobs::getUrl())
            ->assertOk();
    });

    test('non admin diarahkan dari halaman jobs', function () {
        $this->actingAs($this->user);

        $this->get(ListJobs::getUrl())
            ->assertRedirect();
    });
});

it('uses JobResource as its resource', function () {
    $page = new ListJobs();

    $reflection = new ReflectionClass($page);
    $property = $reflection->getProperty('resource');
    $property->setAccessible(true);

    expect($property->getValue($page))->toBe(JobResource::class);
});

it('has no header actions since creating jobs is disabled', function () {
    $this->actingAs($this->admin);

    $page = new ListJobs();

    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderActions');
    $method->setAccessible(true);

    expect($method->invoke($page))->toBe([]);
});

it('lists jobs sorted by latest id first', function () {
    $this->actingAs($this->admin);

    $older = makeListedJob();
    $newer = makeListedJob();

    Livewire::test(ListJobs::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('renders empty state when there are no jobs', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListJobs::class)
        ->assertCountTableRecords(0)
        ->assertSuccessful();
});
