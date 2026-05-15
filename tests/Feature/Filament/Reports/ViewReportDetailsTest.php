<?php

use App\Models\User;
use App\Models\Report;
use Livewire\Livewire;
use App\Models\Product;
use App\Models\LapakProfile;
use App\Models\ProductModeration;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\Reports\Pages\ViewReportDetails;

beforeEach(function () {
    $this->admin = makeUser(isAdmin: true);
});

function makeReport(
    Model $target,
    User $reporter,
    array $overrides = []
): Report {
    return Report::factory()->create(array_merge([
        'user_id'         => $reporter->id,
        'reportable_type' => $target::class,
        'reportable_id'   => $target->id,
        'reason'          => 'Produk palsu',
        'description'     => 'Deskripsi laporan',
        'status'          => 'pending',
    ], $overrides));
}

describe('View Report Details Page', function () {

    it('can mount report detail page for product', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak);

        makeReport($product, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->assertSuccessful()
            ->assertSet('target.id', $product->id);
    });

    it('can mount report detail page for lapak', function () {
        $owner = makeUser();

        $lapak = $owner->lapak;

        makeReport($lapak, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(LapakProfile::class),
            'id'   => $lapak->id,
        ])
            ->assertSuccessful()
            ->assertSet('target.id', $lapak->id);
    });

    it('loads reports collection', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak);

        makeReport($product, makeUser(), [
            'reason' => 'Spam',
        ]);

        makeReport($product, makeUser(), [
            'reason' => 'Barang palsu',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->assertCount('reports', 2);
    });

    it('shows deactivate action for active product', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak, [
            'is_active' => true,
        ]);

        makeReport($product, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->assertActionVisible('deactivateProduct')
            ->assertActionEnabled('deactivateProduct');
    });

    it('disables deactivate action for inactive product', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak, [
            'is_active' => false,
        ]);

        makeReport($product, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->assertActionDisabled('deactivateProduct');
    });

    it('admin can deactivate reported product', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak, [
            'is_active' => true,
        ]);

        makeReport($product, makeUser(), [
            'reason' => 'Produk terlarang',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->callAction('deactivateProduct', [
                'reason'      => 'Melanggar aturan',
                'description' => 'Produk tidak sesuai kebijakan',
            ]);

        expect($product->fresh()->is_active)
            ->toBeFalse();
    });

    it('creates moderation record after deactivate', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak);

        makeReport($product, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->callAction('deactivateProduct', [
                'reason' => 'Pelanggaran',
            ]);

        $this->assertDatabaseHas('product_moderations', [
            'product_id' => $product->id,
            'reason'     => 'Pelanggaran',
            'type'       => ProductModeration::TYPE_DEACTIVATION,
        ]);
    });

    it('deactivate action requires reason', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak);

        makeReport($product, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->callAction('deactivateProduct', [
                'reason' => '',
            ])
            ->assertHasActionErrors([
                'reason' => ['required'],
            ]);
    });

    it('fails when target does not exist', function () {
        $this->actingAs($this->admin);

        expect(fn() => Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => 999999,
        ]))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('shows latest report reason as default deactivate reason', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak);

        makeReport($product, makeUser(), [
            'reason' => 'Barang ilegal',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->mountAction('deactivateProduct')
            ->assertSee('Barang ilegal');
    });

    it('can render infolist sections', function () {
        $owner = makeUser();

        $product = makeProduct($owner->lapak, [
            'title' => 'Produk Testing',
        ]);

        makeReport($product, makeUser());

        $this->actingAs($this->admin);

        Livewire::test(ViewReportDetails::class, [
            'type' => base64_encode(Product::class),
            'id'   => $product->id,
        ])
            ->assertSee('Produk Testing')
            ->assertSee('Produk')
            ->assertSee($owner->name);
    });
});
