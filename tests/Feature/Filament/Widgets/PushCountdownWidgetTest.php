<?php

use App\Filament\Widgets\PushCountdownWidget;
use Livewire\Livewire;

describe('canView', function () {
    it('is visible for non-admin', function () {
        $seller = makeUser();
        $this->actingAs($seller);

        expect(PushCountdownWidget::canView())->toBeTrue();
    });

    it('is hidden for admin', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        expect(PushCountdownWidget::canView())->toBeFalse();
    });
});

describe('render', function () {
    // The remaining-time / "token habis" / "sudah bisa" messages are toggled client-side via
    // Alpine `x-if`, so they can't be asserted here — this only covers the server-rendered stat.
    it('shows the token count as the stat value', function () {
        $seller = makeUser(tokens: 7);
        $this->actingAs($seller);

        Livewire::test(PushCountdownWidget::class)
            ->assertSuccessful()
            ->assertSee('Jumlah Token')
            ->assertSee('7');
    });

    it('renders successfully when tokens are depleted', function () {
        $seller = makeUser(tokens: 0);
        $this->actingAs($seller);

        Livewire::test(PushCountdownWidget::class)
            ->assertSuccessful()
            ->assertSee('Jumlah Token')
            ->assertSee('0');
    });
});
