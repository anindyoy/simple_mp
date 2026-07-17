<?php

use App\Models\User;
use App\Models\JobBatch;
use Livewire\Livewire;
use App\Filament\Resources\JobBatchResource;
use App\Filament\Resources\JobBatchResource\Pages\ListJobBatches;

function makeListedJobBatch(array $attributes = []): JobBatch
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
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

describe('authorization', function () {
    test('admin dapat mengakses halaman job batches', function () {
        $this->actingAs($this->admin);

        $this->get(ListJobBatches::getUrl())
            ->assertOk();
    });

    test('non admin diarahkan dari halaman job batches', function () {
        $this->actingAs($this->user);

        $this->get(ListJobBatches::getUrl())
            ->assertRedirect();
    });
});

it('uses JobBatchResource as its resource', function () {
    $page = new ListJobBatches();

    $reflection = new ReflectionClass($page);
    $property = $reflection->getProperty('resource');
    $property->setAccessible(true);

    expect($property->getValue($page))->toBe(JobBatchResource::class);
});

it('has no header actions since creating job batches is disabled', function () {
    $this->actingAs($this->admin);

    $page = new ListJobBatches();

    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderActions');
    $method->setAccessible(true);

    expect($method->invoke($page))->toBe([]);
});

it('lists job batches sorted by latest creation first', function () {
    $this->actingAs($this->admin);

    $older = makeListedJobBatch(['created_at' => now()->subDay()->timestamp]);
    $newer = makeListedJobBatch(['created_at' => now()->timestamp]);

    Livewire::test(ListJobBatches::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('renders empty state when there are no job batches', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListJobBatches::class)
        ->assertCountTableRecords(0)
        ->assertSuccessful();
});
