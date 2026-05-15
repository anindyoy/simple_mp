<?php

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;


describe('Delete Product', function () {

    it('owner can delete own product from edit page', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertRedirect(ProductResource::getUrl('index'));

        $this->assertModelMissing($product);
    });

    it('user cannot delete other user product via policy', function () {
        $user    = makeUser();
        $other   = makeUser();
        $product = makeProduct($other->lapak);

        $this->actingAs($user);

        expect(ProductResource::canDelete($product))->toBeFalse();
    });

    it('admin cannot edit other user product', function () {
        $admin = makeUser(isAdmin: true);
        $user = makeUser();

        $product = makeProduct($user->lapak);

        $this->actingAs($admin);

        livewire(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->assertForbidden();
    });

    it('can bulk delete own products', function () {
        $user = makeUser();

        $products = collect([
            makeProduct($user->lapak),
            makeProduct($user->lapak),
        ]);

        $this->actingAs($user);

        livewire(ListProducts::class)
            ->callTableBulkAction('delete', $products)
            ->assertSuccessful();

        $products->each(fn($p) => $this->assertModelMissing($p));
    });
});
