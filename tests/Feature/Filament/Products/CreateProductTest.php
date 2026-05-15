<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Pages\CreateProduct;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;


describe('Create Product', function () {

    it('renders create page', function () {
        $user = makeUser();
        $this->actingAs($user);

        livewire(CreateProduct::class)
            ->assertSuccessful();
    });

    it('can create a product with valid data', function () {
        Storage::fake('public');

        $user     = makeUser();
        $category = makeCategory();

        $this->actingAs($user);

        livewire(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title'       => 'Produk Test',
                'description' => 'Deskripsi produk test',
                'price'       => 100000,
                'condition'   => 'baru',
                'is_active'   => true,

                'images' => [
                    UploadedFile::fake()->image('product.jpg'),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'title'    => 'Produk Test',
            'price'    => 100000,
            'lapak_id' => $user->lapak->id,
        ]);
    });

    it('validates required fields on create', function () {
        $user = makeUser();
        $this->actingAs($user);

        livewire(CreateProduct::class)
            ->fillForm([
                'title'       => '',
                'description' => '',
                'price'       => null,
                'category_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title',
                'description',
                'price',
                'category_id',
            ]);
    });

    it('redirects to index after successful create', function () {
        Storage::fake('public');

        $user     = makeUser();
        $category = makeCategory();

        $this->actingAs($user);

        livewire(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title'       => 'Produk Redirect',
                'description' => 'Test redirect',
                'price'       => 50000,
                'condition'   => 'baru',
                'is_active'   => true,

                'images' => [
                    UploadedFile::fake()->image('product.jpg'),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(ProductResource::getUrl('index'));
    });
});
