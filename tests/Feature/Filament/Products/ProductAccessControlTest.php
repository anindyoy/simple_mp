<?php

use App\Filament\Resources\Products\ProductResource;

use function Pest\Laravel\actingAs;

require_once __DIR__ . '/helpers.php';

describe('Product Access Control', function () {

    it('user can view own product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        expect(ProductResource::canView($product))->toBeTrue();
    });

    it('user cannot view other user product', function () {
        $user    = makeUser();
        $other   = makeUser();
        $product = makeProduct($other->lapak);

        $this->actingAs($user);

        expect(ProductResource::canView($product))->toBeFalse();
    });

    it('user can edit own product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        expect(ProductResource::canEdit($product))->toBeTrue();
    });

    it('user cannot edit other user product', function () {
        $user    = makeUser();
        $other   = makeUser();
        $product = makeProduct($other->lapak);

        $this->actingAs($user);

        expect(ProductResource::canEdit($product))->toBeFalse();
    });

    it('user can delete own product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        expect(ProductResource::canDelete($product))->toBeTrue();
    });

    it('user cannot delete other user product', function () {
        $user    = makeUser();
        $other   = makeUser();
        $product = makeProduct($other->lapak);

        $this->actingAs($user);

        expect(ProductResource::canDelete($product))->toBeFalse();
    });

    it('getEloquentQuery returns no records when unauthenticated', function () {
        $query = ProductResource::getEloquentQuery();

        expect($query->count())->toBe(0);
    });

    it('getEloquentQuery returns all products for admin', function () {
        $admin = makeUser(isAdmin: true);
        $user  = makeUser();

        makeProduct($user->lapak);
        makeProduct($user->lapak);

        $this->actingAs($admin);

        expect(ProductResource::getEloquentQuery()->count())
            ->toBeGreaterThanOrEqual(2);
    });

    it('getEloquentQuery returns only own products for regular user', function () {
        $user  = makeUser();
        $other = makeUser();

        makeProduct($user->lapak);
        makeProduct($other->lapak);

        $this->actingAs($user);

        $lapakIds = ProductResource::getEloquentQuery()
            ->pluck('lapak_id')
            ->unique();

        expect($lapakIds)->toContain($user->lapak->id)
            ->not->toContain($other->lapak->id);
    });
});