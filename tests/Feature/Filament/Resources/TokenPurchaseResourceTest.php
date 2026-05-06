<?php

use App\Models\User;
use App\Models\TokenPurchase;
use Illuminate\Support\Facades\Notification;
use Filament\Notifications\Notification as FilamentNotification;

beforeEach(function () {
    // Setup test users
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

it('restricts token purchase resource access to authenticated users', function () {
    $result = call_user_func([
        'App\Filament\Resources\TokenPurchases\TokenPurchaseResource',
        'canViewAny',
    ]);

    expect($result)->toBeFalse();

    $this->actingAs($this->user);
    $result = call_user_func([
        'App\Filament\Resources\TokenPurchases\TokenPurchaseResource',
        'canViewAny',
    ]);

    expect($result)->toBeTrue();
});

it('prevents creation of token purchases', function () {
    $this->actingAs($this->admin);

    $result = call_user_func([
        'App\Filament\Resources\TokenPurchases\TokenPurchaseResource',
        'canCreate',
    ]);

    expect($result)->toBeFalse();
});

it('restricts token purchase editing to admins only', function () {
    $purchase = TokenPurchase::factory()->for($this->user)->create();

    // Non-admin cannot edit
    $this->actingAs($this->user);
    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canEdit'],
        $purchase
    );
    expect($result)->toBeFalse();

    // Admin can edit
    $this->actingAs($this->admin);
    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canEdit'],
        $purchase
    );
    expect($result)->toBeTrue();
});

it('allows users to view only their own purchases', function () {
    $userPurchase = TokenPurchase::factory()->for($this->user)->create();
    $otherPurchase = TokenPurchase::factory()->for($this->admin)->create();

    // User can view their own
    $this->actingAs($this->user);
    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canView'],
        $userPurchase
    );
    expect($result)->toBeTrue();

    // User cannot view others'
    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canView'],
        $otherPurchase
    );
    expect($result)->toBeFalse();

    // Admin can view all
    $this->actingAs($this->admin);
    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canView'],
        $userPurchase
    );
    expect($result)->toBeTrue();

    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canView'],
        $otherPurchase
    );
    expect($result)->toBeTrue();
});

it('prevents deletion of token purchases', function () {
    $purchase = TokenPurchase::factory()->for($this->user)->create();

    $this->actingAs($this->admin);
    $result = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'canDelete'],
        $purchase
    );

    expect($result)->toBeFalse();
});

it('filters eloquent query to show only user own purchases for non-admins', function () {
    TokenPurchase::factory()->for($this->user)->count(3)->create();
    TokenPurchase::factory()->for($this->admin)->count(2)->create();

    // Admin sees all
    $this->actingAs($this->admin);
    $query = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'getEloquentQuery']
    );
    expect($query->count())->toBe(5);

    // User sees only their own
    $this->actingAs($this->user);
    $query = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'getEloquentQuery']
    );
    expect($query->count())->toBe(3);
    expect($query->pluck('user_id')->unique()->toArray())->toBe([$this->user->id]);
});

it('confirms pending token purchase and adds tokens to user', function () {
    $purchase = TokenPurchase::factory()
        ->for($this->user)
        ->create([
            'status' => 'pending',
            'quantity' => 50,
            'confirmed_at' => null,
        ]);

    $initialTokens = $this->user->push_tokens;

    // Execute confirmPurchase
    $reflectionMethod = new ReflectionMethod(
        'App\Filament\Resources\TokenPurchases\TokenPurchaseResource',
        'confirmPurchase'
    );
    $reflectionMethod->setAccessible(true);
    $reflectionMethod->invoke(null, $purchase);

    // Verify purchase was updated
    $purchase->refresh();
    expect($purchase->status)->toBe('confirmed');
    expect($purchase->confirmed_at)->not->toBeNull();

    // Verify tokens were added
    $this->user->refresh();
    expect($this->user->push_tokens)->toBe($initialTokens + 50);
});

it('cancels pending token purchase with notes', function () {
    $purchase = TokenPurchase::factory()
        ->for($this->user)
        ->create([
            'status' => 'pending',
            'notes' => null,
        ]);

    $cancellationReason = 'Bukti pembayaran tidak valid';

    // Execute cancelPurchase
    $reflectionMethod = new ReflectionMethod(
        'App\Filament\Resources\TokenPurchases\TokenPurchaseResource',
        'cancelPurchase'
    );
    $reflectionMethod->setAccessible(true);
    $reflectionMethod->invoke(null, $purchase, $cancellationReason);

    // Verify purchase was updated
    $purchase->refresh();
    expect($purchase->status)->toBe('cancelled');
    expect($purchase->notes)->toBe($cancellationReason);
});

it('loads token purchase with user relationship in query', function () {
    $purchase = TokenPurchase::factory()->for($this->user)->create();

    $this->actingAs($this->admin);
    $query = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'getEloquentQuery']
    );

    $loaded = $query->with('user')->find($purchase->id);
    expect($loaded->user)->toBeInstanceOf(User::class);
    expect($loaded->user->id)->toBe($this->user->id);
});

it('filters purchases by status in table configuration', function () {
    $pending = TokenPurchase::factory()->for($this->user)->create(['status' => 'pending']);
    $confirmed = TokenPurchase::factory()->for($this->user)->create(['status' => 'confirmed']);
    $cancelled = TokenPurchase::factory()->for($this->user)->create(['status' => 'cancelled']);

    $this->actingAs($this->user);
    $query = call_user_func(
        ['App\Filament\Resources\TokenPurchases\TokenPurchaseResource', 'getEloquentQuery']
    );

    expect($query->count())->toBe(3);
    expect(TokenPurchase::where('user_id', $this->user->id)->where('status', 'pending')->count())->toBe(1);
    expect(TokenPurchase::where('user_id', $this->user->id)->where('status', 'confirmed')->count())->toBe(1);
    expect(TokenPurchase::where('user_id', $this->user->id)->where('status', 'cancelled')->count())->toBe(1);
});
