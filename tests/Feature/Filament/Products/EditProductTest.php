<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\Products\Pages\EditProduct;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

require_once __DIR__ . '/helpers.php';

describe('Edit Product', function () {

    it('renders edit page for owner', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful();
    });

    it('can update product with valid data', function () {
        Storage::fake('public');

        $user     = makeUser();
        $product  = makeProduct($user->lapak);
        $category = makeCategory();

        $this->actingAs($user);

        livewire(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->fillForm([
                'title'       => 'Judul Diperbarui',
                'description' => 'Deskripsi diperbarui',
                'price'       => 200000,
                'category_id' => $category->id,

                'condition' => $category->supportsCondition()
                    ? 'baru'
                    : null,

                'images' => [
                    UploadedFile::fake()->image('updated.jpg'),
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($product->fresh()->title)->toBe('Judul Diperbarui');
        expect($product->fresh()->price)->toBe(200000);
    });

    it('validates required fields on edit', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'title'       => '',
                'description' => '',
                'price'       => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['title', 'description', 'price']);
    });

    it('populates form with existing product data', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['title' => 'Produk Lama', 'price' => 75000]);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'title' => 'Produk Lama',
                'price' => 75000,
            ]);
    });

    it('shows requestReactivation action for inactive product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertActionVisible('requestReactivation');
    });

    it('hides requestReactivation action for active product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => true]);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertActionHidden('requestReactivation');
    });

    it('disables requestReactivation action when pending request exists', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        makePendingModeration($product);

        $this->actingAs($user);

        livewire(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertActionDisabled('requestReactivation');
    });

    it('admin cannot access edit page even with pending request', function () {
        $admin   = makeUser(isAdmin: true);
        $user    = makeUser();

        $product = makeProduct($user->lapak, [
            'is_active' => false,
        ]);

        makePendingModeration($product, 'Siap ditinjau');

        $this->actingAs($admin);

        livewire(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->assertForbidden();
    });

    it('hides approveReactivation and rejectReactivation when no pending request', function () {
        $user = makeUser();

        $product = makeProduct($user->lapak, [
            'is_active' => false,
        ]);

        $this->actingAs($user);

        livewire(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->assertActionHidden('approveReactivation')
            ->assertActionHidden('rejectReactivation');
    });
});
