<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\TokenPurchase;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\TokenPurchases\TokenPurchaseResource;
use App\Filament\Resources\TokenPurchases\Pages\ViewTokenPurchase;
use App\Filament\Resources\TokenPurchases\Pages\ListTokenPurchases;

beforeEach(function () {
    Storage::fake('public');
});

function makeTokenPurchase(
    User $user,
    array $overrides = []
): TokenPurchase {
    return TokenPurchase::factory()->create(array_merge([
        'user_id'         => $user->id,
        'quantity'        => 10,
        'total_price'     => 50000,
        'status'          => 'pending',
        'payment_method'  => 'bank_transfer',
        'bank_account'    => 'BCA - 123456789',
        'proof_of_payment' => 'token-purchases/proof/test.jpg',
    ], $overrides));
}

describe('Token Purchase Resource', function () {

    it('guest cannot access token purchases', function () {
        $this->get(TokenPurchaseResource::getUrl('index'))
            ->assertRedirect();
    });

    it('user can access own token purchases', function () {
        $user = makeUser();

        $this->actingAs($user);

        $this->get(TokenPurchaseResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('user only sees own purchases', function () {
        $user = makeUser();
        $otherUser = makeUser();

        $ownPurchase = makeTokenPurchase($user);
        $otherPurchase = makeTokenPurchase($otherUser);

        $this->actingAs($user);

        Livewire::test(ListTokenPurchases::class)
            ->loadTable()
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$ownPurchase])
            ->assertCanNotSeeTableRecords([$otherPurchase]);
    });

    it('admin can see all purchases', function () {
        $admin = makeUser(isAdmin: true);

        $purchase1 = makeTokenPurchase(makeUser());
        $purchase2 = makeTokenPurchase(makeUser());

        $this->actingAs($admin);

        Livewire::test(ListTokenPurchases::class)
            ->loadTable()
            ->removeTableFilters()
            ->assertCanSeeTableRecords([
                $purchase1,
                $purchase2,
            ]);
    });

    it('user can view own purchase', function () {
        $user = makeUser();

        $purchase = makeTokenPurchase($user);

        $this->actingAs($user);

        $this->get(
            TokenPurchaseResource::getUrl('view', [
                'record' => $purchase,
            ])
        )->assertSuccessful();
    });

    it('user cannot view other user purchase', function () {
        $user = makeUser();
        $otherUser = makeUser();

        $purchase = makeTokenPurchase($otherUser);

        $this->actingAs($user);

        $this->get(
            TokenPurchaseResource::getUrl('view', [
                'record' => $purchase,
            ])
        )->assertNotFound();
    });

    it('admin can view any purchase', function () {
        $admin = makeUser(isAdmin: true);

        $purchase = makeTokenPurchase(makeUser());

        $this->actingAs($admin);

        $this->get(
            TokenPurchaseResource::getUrl('view', [
                'record' => $purchase,
            ])
        )->assertSuccessful();
    });

    it('user cannot edit purchase', function () {
        $user = makeUser();

        $purchase = makeTokenPurchase($user);

        $this->actingAs($user);

        expect(
            TokenPurchaseResource::canEdit($purchase)
        )->toBeFalse();
    });

    it('admin can edit purchase', function () {
        $admin = makeUser(isAdmin: true);

        $purchase = makeTokenPurchase(makeUser());

        $this->actingAs($admin);

        expect(
            TokenPurchaseResource::canEdit($purchase)
        )->toBeTrue();
    });

    it('admin can confirm pending purchase', function () {
        $admin = makeUser(isAdmin: true);

        $purchase = makeTokenPurchase(makeUser(), [
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $confirmPurchase = new ReflectionMethod(TokenPurchaseResource::class, 'confirmPurchase');
        $confirmPurchase->setAccessible(true);
        $confirmPurchase->invoke(null, $purchase);

        expect($purchase->fresh()->status)
            ->toBe('confirmed');

        expect($purchase->fresh()->confirmed_at)
            ->not->toBeNull();
    });

    it('admin can cancel pending purchase', function () {
        $admin = makeUser(isAdmin: true);

        $purchase = makeTokenPurchase(makeUser(), [
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $cancelPurchase = new ReflectionMethod(TokenPurchaseResource::class, 'cancelPurchase');
        $cancelPurchase->setAccessible(true);
        $cancelPurchase->invoke(null, $purchase, 'Pembayaran tidak valid');

        expect($purchase->fresh()->status)
            ->toBe('cancelled');

        expect($purchase->fresh()->notes)
            ->toBe('Pembayaran tidak valid');
    });

    it('confirm action only visible for pending purchase', function () {
        $admin = makeUser(isAdmin: true);

        $pendingPurchase = makeTokenPurchase(makeUser(), [
            'status' => 'pending',
        ]);

        $confirmedPurchase = makeTokenPurchase(makeUser(), [
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListTokenPurchases::class)
            ->loadTable()
            ->removeTableFilters()
            ->assertTableActionVisible('confirm', $pendingPurchase)
            ->assertTableActionHidden('confirm', $confirmedPurchase);
    });

    it('cancel action only visible for pending purchase', function () {
        $admin = makeUser(isAdmin: true);

        $pendingPurchase = makeTokenPurchase(makeUser(), [
            'status' => 'pending',
        ]);

        $cancelledPurchase = makeTokenPurchase(makeUser(), [
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListTokenPurchases::class)
            ->loadTable()
            ->removeTableFilters()
            ->assertTableActionVisible('cancel', $pendingPurchase)
            ->assertTableActionHidden('cancel', $cancelledPurchase);
    });

    it('user cannot delete purchase', function () {
        $user = makeUser();

        $purchase = makeTokenPurchase($user);

        $this->actingAs($user);

        expect(
            TokenPurchaseResource::canDelete($purchase)
        )->toBeFalse();
    });

    it('resource cannot create purchase from filament', function () {
        expect(
            TokenPurchaseResource::canCreate()
        )->toBeFalse();
    });

    it('view page loads successfully', function () {
        $user = makeUser();

        $purchase = makeTokenPurchase($user);

        $this->actingAs($user);

        Livewire::test(ViewTokenPurchase::class, [
            'record' => $purchase->getRouteKey(),
        ])
            ->assertSuccessful();
    });
});
