<?php

use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\RandomProductHistory;
use Illuminate\Support\Facades\Cache;
use App\Filament\Pages\RandomProductPickerPage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Cache::flush();
    RandomProductHistory::truncate();
});

// ── helpers ──────────────────────────────────────────────────────────────────

/**
 * Generate history entries for given products.
 */
function createHistory(mixed ...$products): void
{
    foreach ($products as $product) {
        RandomProductHistory::create([
            'product_id' => $product->id,
            'user_id'    => null,
            'created_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }
}

// ── canAccess / shouldRegisterNavigation ────────────────────────────────────────

describe('canAccess', function () {
    it('returns true for an admin user', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        expect(RandomProductPickerPage::canAccess())->toBeTrue();
        expect(RandomProductPickerPage::shouldRegisterNavigation())->toBeTrue();
    });

    it('returns false for a regular user', function () {
        $user = makeUser();
        $this->actingAs($user);

        expect(RandomProductPickerPage::canAccess())->toBeFalse();
        expect(RandomProductPickerPage::shouldRegisterNavigation())->toBeFalse();
    });

    it('returns false for a guest', function () {
        expect(RandomProductPickerPage::canAccess())->toBeFalse();
    });
});

// ── mount ────────────────────────────────────────────────────────────────────────

describe('mount', function () {
    it('aborts with 403 when not admin', function () {
        $user = makeUser();
        $this->actingAs($user);

        expect(fn () => (new RandomProductPickerPage())->mount())
            ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('renders successfully for an admin', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        livewire(RandomProductPickerPage::class)->assertSuccessful();
    });
});

// ── generate ─────────────────────────────────────────────────────────────────────

describe('generate', function () {
    it('picks an eligible active product', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('hasGenerated'))->toBeTrue();
        expect($component->get('product')?->id)->toBe($product->id);
    });

    it('saves a history record when a product is picked', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak);

        $this->actingAs($admin);

        livewire(RandomProductPickerPage::class)->call('generate');

        expect(RandomProductHistory::count())->toBe(1);
        expect(RandomProductHistory::first()->product_id)->toBe($product->id);
        expect(RandomProductHistory::first()->user_id)->toBe($admin->id);
    });

    it('does not save history when no product is found', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        livewire(RandomProductPickerPage::class)->call('generate');

        expect(RandomProductHistory::count())->toBe(0);
    });

    it('does not pick a product from the last 10 history entries', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $lapak = $seller->lapak;

        // The schedule delays 4h between products per lapak, so we create
        // products spaced 4h apart so all become eligible.
        $now = now();
        $products = [];
        for ($i = 0; $i < 12; $i++) {
            $products[] = makeProduct($lapak, [
                'created_at' => (clone $now)->subHours(4 * (12 - $i)),
            ]);
        }

        // Put first 10 products into history
        $recent = array_slice($products, 0, 10);
        createHistory(...$recent);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('hasGenerated'))->toBeTrue();
        expect($component->get('product'))->not->toBeNull();

        $pickedId = $component->get('product')->id;

        // Must NOT be one of the 10 in history
        expect(collect($recent)->pluck('id'))->not->toContain($pickedId);
    });

    it('falls back to any eligible product when all are in recent history', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $lapak = $seller->lapak;

        // All 3 products spaced 4h apart so all are eligible
        $now = now();
        $products = [];
        for ($i = 0; $i < 3; $i++) {
            $products[] = makeProduct($lapak, [
                'created_at' => (clone $now)->subHours(4 * (3 - $i)),
            ]);
        }
        createHistory(...$products);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('hasGenerated'))->toBeTrue();
        // Should still pick something (fallback)
        expect($component->get('product'))->not->toBeNull();
        expect(collect($products)->pluck('id'))->toContain($component->get('product')->id);
    });

    it('loads history on mount', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $lapak = $seller->lapak;

        $now = now();
        $p1 = makeProduct($lapak, ['created_at' => (clone $now)->subHours(12)]);
        $p2 = makeProduct($lapak, ['created_at' => (clone $now)->subHours(8)]);
        $p3 = makeProduct($lapak, ['created_at' => (clone $now)->subHours(4)]);

        createHistory($p1, $p2, $p3);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        /** @var \Illuminate\Support\Collection $history */
        $history = $component->get('history');

        expect($history)->toHaveCount(3);
        expect($history->pluck('product_id')->toArray())->toEqualCanonicalizing([$p1->id, $p2->id, $p3->id]);
    });

    it('never picks a product marked inactive', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        makeProduct($seller->lapak, ['is_active' => false]);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('hasGenerated'))->toBeTrue();
        expect($component->get('product'))->toBeNull();
    });

    it('never picks a product belonging to an inactive lapak', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $seller->lapak->update(['is_active' => false]);
        makeProduct($seller->lapak->fresh());

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('product'))->toBeNull();
    });

    it('sets hasGenerated true with a null product when nothing is eligible', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('hasGenerated'))->toBeTrue();
        expect($component->get('product'))->toBeNull();
    });
});

// ── buildHistoryCopyText ─────────────────────────────────────────────────────────

describe('buildHistoryCopyText', function () {
    it('returns formatted product text without the "Produk Pilihan Hari Ini" header', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak, [
            'title' => 'Gadget X',
            'price' => 250000,
        ]);

        $this->actingAs($admin);

        $page = new RandomProductPickerPage();
        $text = $page->buildHistoryCopyText($product->fresh(['category', 'lapak']));

        expect($text)->toContain('Nama Produk: Gadget X')
            ->toContain('Rp 250.000')
            ->toContain('Lapak: ' . $seller->lapak->name)
            ->toContain(route('product.show', $product))
            ->not->toContain('Produk Pilihan Hari Ini');
    });
});

// ── getCopyText ──────────────────────────────────────────────────────────────────

describe('getCopyText', function () {
    it('returns an empty string when no product has been generated', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $page = new RandomProductPickerPage();

        expect($page->getCopyText())->toBe('');
    });

    it('includes full product info but not the description', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak, [
            'title' => 'Sepeda Ontel Antik',
            'description' => 'RAHASIA_DESKRIPSI_TIDAK_BOLEH_MUNCUL',
            'price' => 150000,
        ]);

        $this->actingAs($admin);

        $page = new RandomProductPickerPage();
        $page->product = $product->fresh(['category', 'lapak']);

        $text = $page->getCopyText();

        expect($text)->toContain('Sepeda Ontel Antik')
            ->toContain('Rp 150.000')
            ->toContain('Lapak: ' . $seller->lapak->name)
            ->toContain(route('product.show', $product))
            ->not->toContain('RAHASIA_DESKRIPSI_TIDAK_BOLEH_MUNCUL');
    });
});
