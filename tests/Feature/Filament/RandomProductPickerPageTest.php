<?php

use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\RandomProductHistory;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use App\Filament\Pages\RandomProductPickerPage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Cache::flush();
    RandomProductHistory::truncate();
    Setting::query()->delete();
});

// ── helpers ──────────────────────────────────────────────────────────────────

/**
 * Generate history entries for given products, oldest first (last argument = most recent).
 */
function createHistory(mixed ...$products): void
{
    $count = count($products);

    foreach ($products as $index => $product) {
        RandomProductHistory::create([
            'product_id' => $product->id,
            'lapak_id'   => $product->lapak_id,
            'user_id'    => null,
            'created_at' => now()->subMinutes($count - $index),
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

    it('defaults maxProductHistory and maxLapakHistory to the class defaults when no setting is stored', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        expect($component->get('maxProductHistory'))->toBe(10);
        expect($component->get('maxLapakHistory'))->toBe(7);
    });

    it('loads maxProductHistory and maxLapakHistory from the setting table when stored', function () {
        Setting::setValue('random_product_picker_max_product_history', '5');
        Setting::setValue('random_product_picker_max_lapak_history', '3');

        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        expect($component->get('maxProductHistory'))->toBe(5);
        expect($component->get('maxLapakHistory'))->toBe(3);
    });

    it('computes active product and lapak counts', function () {
        $admin = makeUser(isAdmin: true); // has its own active lapak, no products
        $seller1 = makeUser();
        $seller2 = makeUser();
        $seller2->lapak->update(['is_active' => false]);

        makeProduct($seller1->lapak);
        makeProduct($seller1->lapak, ['created_at' => now()->subHours(4)]);
        makeProduct($seller1->lapak, ['is_active' => false, 'created_at' => now()->subHours(8)]);
        makeProduct($seller2->lapak->fresh());

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        // 2 active products from seller1 + 1 active product from seller2 (lapak inactive doesn't affect product flag)
        expect($component->get('activeProductCount'))->toBe(3);
        // admin's own lapak + seller1's lapak are active; seller2's lapak is inactive
        expect($component->get('activeLapakCount'))->toBe(2);
    });

    it('recommends 50% of active product/lapak counts, rounded', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $page = new RandomProductPickerPage();
        $page->activeProductCount = 11;
        $page->activeLapakCount = 5;

        expect($page->recommendedMaxProductHistory())->toBe(6);
        expect($page->recommendedMaxLapakHistory())->toBe(3);
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
        expect(RandomProductHistory::first()->lapak_id)->toBe($seller->lapak->id);
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

        // Each product belongs to its own lapak so the new "same store in
        // the last 7 picks" rule does not interfere with this product-level check.
        $now = now();
        $products = [];
        for ($i = 0; $i < 12; $i++) {
            $seller = makeUser();
            $products[] = makeProduct($seller->lapak, [
                'created_at' => (clone $now)->subHours(4),
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

    it('does not pick a product from a lapak that appeared in the last 7 history entries', function () {
        $admin = makeUser(isAdmin: true);

        // 8 different lapaks, one product each, all eligible immediately.
        $sellers = [];
        $products = [];
        for ($i = 0; $i < 8; $i++) {
            $sellers[] = $seller = makeUser();
            $products[] = makeProduct($seller->lapak, [
                'created_at' => now()->subHours(4),
            ]);
        }

        // Put the first 7 products' lapaks into the most recent history.
        $recent = array_slice($products, 0, 7);
        createHistory(...$recent);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)->call('generate');

        expect($component->get('hasGenerated'))->toBeTrue();
        expect($component->get('product'))->not->toBeNull();

        $pickedLapakId = $component->get('product')->lapak_id;

        // Must NOT belong to one of the 7 lapaks in the recent history.
        expect(collect($recent)->pluck('lapak_id'))->not->toContain($pickedLapakId);
        // The only remaining eligible product is the 8th one.
        expect($component->get('product')->id)->toBe($products[7]->id);
    });

    it('respects custom maxProductHistory and maxLapakHistory values', function () {
        $admin = makeUser(isAdmin: true);

        // 3 products in the same lapak, spaced so all are eligible.
        $seller = makeUser();
        $now = now();
        $products = [];
        for ($i = 0; $i < 3; $i++) {
            $products[] = makeProduct($seller->lapak, [
                'created_at' => (clone $now)->subHours(4 * (3 - $i)),
            ]);
        }

        // History (oldest -> newest): products[0], products[1]
        createHistory($products[0], $products[1]);

        $this->actingAs($admin);

        // With maxProductHistory = 1 only the most recent entry (products[1]) is
        // excluded by product id, and maxLapakHistory = 0 disables the store rule
        // entirely, so products[0] becomes eligible again.
        $component = livewire(RandomProductPickerPage::class)
            ->set('maxProductHistory', 1)
            ->set('maxLapakHistory', 0)
            ->call('generate');

        expect($component->get('product'))->not->toBeNull();
        expect($component->get('product')->id)->not->toBe($products[1]->id);
    });

    it('persists maxProductHistory and maxLapakHistory to the setting table on generate', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        makeProduct($seller->lapak);

        $this->actingAs($admin);

        livewire(RandomProductPickerPage::class)
            ->set('maxProductHistory', 4)
            ->set('maxLapakHistory', 2)
            ->call('generate');

        expect(Setting::getValue('random_product_picker_max_product_history'))->toBe('4');
        expect(Setting::getValue('random_product_picker_max_lapak_history'))->toBe('2');
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

// ── pushProduct ──────────────────────────────────────────────────────────────────

describe('pushProduct', function () {
    it('updates pushed_at and sets pushed_by to system', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak, ['pushed_at' => null, 'pushed_by' => null]);

        $this->actingAs($admin);

        livewire(RandomProductPickerPage::class)->call('pushProduct', $product->id);

        $product->refresh();

        expect($product->pushed_at)->not->toBeNull();
        expect($product->pushed_at->diffInSeconds(now()))->toBeLessThan(5);
        expect($product->pushed_by)->toBe('system');
    });

    it('shows a success notification', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak);

        $this->actingAs($admin);

        livewire(RandomProductPickerPage::class)
            ->call('pushProduct', $product->id)
            ->assertNotified();
    });
});

// ── search / filter history ──────────────────────────────────────────────────────

describe('search', function () {
    it('filters history by product title', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $p1 = makeProduct($seller->lapak, ['title' => 'Gadget Canggih']);
        $p2 = makeProduct($seller->lapak, ['title' => 'Makanan Enak']);

        createHistory($p1, $p2);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)
            ->set('search', 'Gadget');

        /** @var \Illuminate\Support\Collection $history */
        $history = $component->get('history');

        expect($history)->toHaveCount(1);
        expect($history->first()->product_id)->toBe($p1->id);
    });

    it('filters history by lapak name', function () {
        $admin = makeUser(isAdmin: true);
        $seller1 = makeUser();
        $seller1->lapak->update(['name' => 'Toko Jaya']);
        $seller2 = makeUser();
        $seller2->lapak->update(['name' => 'Warung Makmur']);

        $p1 = makeProduct($seller1->lapak->fresh(), ['title' => 'Produk A']);
        $p2 = makeProduct($seller2->lapak->fresh(), ['title' => 'Produk B']);

        createHistory($p1, $p2);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class)
            ->set('search', 'Makmur');

        /** @var \Illuminate\Support\Collection $history */
        $history = $component->get('history');

        expect($history)->toHaveCount(1);
        expect($history->first()->product_id)->toBe($p2->id);
    });

    it('resets to page 1 when search changes', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        // Create 30 history entries
        $products = [];
        for ($i = 0; $i < 30; $i++) {
            $products[] = makeProduct($seller->lapak, ['title' => 'Produk ' . $i]);
        }
        createHistory(...$products);

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        // Go to page 2
        $component->call('nextPage');
        expect($component->get('page'))->toBe(2);

        // Change search — should reset to page 1
        $component->set('search', 'Produk 0');

        expect($component->get('page'))->toBe(1);
    });
});

// ── pagination (prevPage / nextPage) ─────────────────────────────────────────────

describe('pagination', function () {
    it('starts at page 1', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        expect($component->get('page'))->toBe(1);
    });

    it('goes to page 2 with nextPage', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        // Need more than 20 entries to have page 2
        for ($i = 0; $i < 25; $i++) {
            $p = makeProduct($seller->lapak);
            createHistory($p);
        }

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        $component->call('nextPage');
        expect($component->get('page'))->toBe(2);

        $history = $component->get('history');
        expect($history)->toHaveCount(5); // remaining items
    });

    it('does not go below page 1 with prevPage', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        $component->call('prevPage');
        expect($component->get('page'))->toBe(1);
    });

    it('tracks totalHistory correctly', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        for ($i = 0; $i < 5; $i++) {
            $p = makeProduct($seller->lapak);
            createHistory($p);
        }

        $this->actingAs($admin);

        $component = livewire(RandomProductPickerPage::class);

        expect($component->get('totalHistory'))->toBe(5);
    });
});