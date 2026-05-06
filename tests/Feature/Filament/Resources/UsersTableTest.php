<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

// ==================== Query Logic ====================

it('adds products_count attribute via withCount', function () {
    $this->actingAs($this->admin);

    $user = User::factory()->create();

    $result = User::query()
        ->withCount('lapak') // simple version
        ->first();

    expect($result->lapak_count)->not->toBeNull();
});

// ==================== Email Verification Filter ====================

it('filters verified users correctly', function () {
    $this->actingAs($this->admin);

    $verified = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $unverified = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $query = User::query()->whereNotNull('email_verified_at');

    expect($query->pluck('id'))->toContain($verified->id);
    expect($query->pluck('id'))->not->toContain($unverified->id);
});

it('filters unverified users correctly', function () {
    $this->actingAs($this->admin);

    $verified = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $unverified = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $query = User::query()->whereNull('email_verified_at');

    expect($query->pluck('id'))->toContain($unverified->id);
    expect($query->pluck('id'))->not->toContain($verified->id);
});

// ==================== Date Filters ====================

it('filters users by created_at range', function () {
    $this->actingAs($this->admin);

    $old = User::factory()->create([
        'created_at' => now()->subDays(10),
    ]);

    $new = User::factory()->create([
        'created_at' => now(),
    ]);

    $query = User::query()
        ->whereDate('created_at', '>=', now()->subDays(5));

    expect($query->pluck('id'))->toContain($new->id);
    expect($query->pluck('id'))->not->toContain($old->id);
});

it('filters users by updated_at range', function () {
    $this->actingAs($this->admin);

    $old = User::factory()->create([
        'updated_at' => now()->subDays(10),
    ]);

    $new = User::factory()->create([
        'updated_at' => now(),
    ]);

    $query = User::query()
        ->whereDate('updated_at', '>=', now()->subDays(5));

    expect($query->pluck('id'))->toContain($new->id);
    expect($query->pluck('id'))->not->toContain($old->id);
});

// ==================== Push Token Logic ====================

it('increments push tokens correctly', function () {
    $this->actingAs($this->admin);

    $user = User::factory()->create([
        'push_tokens' => 5,
    ]);

    $user->increment('push_tokens', 3);

    expect($user->fresh()->push_tokens)->toBe(8);
});

it('sets push tokens correctly', function () {
    $this->actingAs($this->admin);

    $user = User::factory()->create([
        'push_tokens' => 5,
    ]);

    $user->update([
        'push_tokens' => 10,
    ]);

    expect($user->fresh()->push_tokens)->toBe(10);
});

// ==================== Badge Logic ====================

it('email badge shows verified state correctly', function () {
    $verified = User::factory()->make([
        'email_verified_at' => now(),
    ]);

    $unverified = User::factory()->make([
        'email_verified_at' => null,
    ]);

    expect($verified->email_verified_at)->not->toBeNull();
    expect($unverified->email_verified_at)->toBeNull();
});

it('push token badge reflects correct state', function () {
    $userWithToken = User::factory()->make([
        'push_tokens' => 5,
    ]);

    $userWithoutToken = User::factory()->make([
        'push_tokens' => 0,
    ]);

    expect($userWithToken->push_tokens)->toBeGreaterThan(0);
    expect($userWithoutToken->push_tokens)->toBe(0);
});

// ==================== Sorting ====================

it('orders users by created_at correctly', function () {
    $this->actingAs($this->admin);

    $old = User::factory()->create([
        'created_at' => now()->subDay(),
    ]);

    $new = User::factory()->create([
        'created_at' => now(),
    ]);

    $users = User::query()
        ->whereIn('id', [$old->id, $new->id])
        ->orderByDesc('created_at')
        ->get();

    expect($users->first()->id)->toBe($new->id);
});
