<?php

use App\Filament\Resources\Products\Pages\ListProducts;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;


describe('List Products', function () {

    it('renders list page for regular user', function () {
        $user = makeUser();
        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertSuccessful();
    });

    it('renders list page for admin', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertSuccessful();
    });

    it('shows only own products to regular user', function () {
        $user  = makeUser();
        $other = makeUser();

        $ownProduct   = makeProduct($user->lapak);
        $otherProduct = makeProduct($other->lapak);

        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertCanSeeTableRecords([$ownProduct])
            ->assertCanNotSeeTableRecords([$otherProduct]);
    });

    it('shows all products to admin', function () {
        $admin   = makeUser(isAdmin: true);
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertCanSeeTableRecords([$product]);
    });

    it('hides create button for admin', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertActionHidden('create');
    });

    it('shows create button for regular user', function () {
        $user = makeUser();
        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertActionVisible('create');
    });
});