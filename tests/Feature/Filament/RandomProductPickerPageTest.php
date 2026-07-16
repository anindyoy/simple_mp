<?php

use App\Filament\Pages\RandomProductPickerPage;
use App\Models\LapakProfile;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Cache::flush();
});

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
