<?php

use App\Policies\ProductPolicy;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

// ── helpers ────────────────────────────────────────────────────────────────────

// The Product::creating callback always sets pushed_at = now() - 2h, so
// nextPushAt = pushed_at + 6h = creation_time + 4h.
// These helpers compute expected values from the actual stored pushed_at.

function pushedAtOf(\App\Models\Product $product): Carbon
{
    return $product->fresh()->pushed_at;
}

function nextPushAtOf(\App\Models\Product $product): Carbon
{
    return pushedAtOf($product)->addHours(6);
}

// ── formattedRemainingPushCooldown ─────────────────────────────────────────────

describe('formattedRemainingPushCooldown', function () {
    it('returns "0 detik" when there is no push history', function () {
        $user = makeUser();
        $this->actingAs($user);

        expect(ProductPolicy::formattedRemainingPushCooldown())->toBe('0 detik');
    });

    it('formats hours only', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        // now = creation_time, nextPushAt = creation_time + 4h → remaining = 4h exactly
        Carbon::setTestNow($base);

        expect(ProductPolicy::formattedRemainingPushCooldown())->toBe('4 jam');
    });

    it('formats minutes only', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        // nextPushAt = base + 4h = 14:00, advance to 13:30 → remaining = 30m
        Carbon::setTestNow($base->copy()->addHours(3)->addMinutes(30));

        expect(ProductPolicy::formattedRemainingPushCooldown())->toBe('30 menit');
    });

    it('formats seconds only', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        // nextPushAt = base + 4h = 14:00:00, advance to 13:59:30 → remaining = 30s
        Carbon::setTestNow($base->copy()->addHours(3)->addMinutes(59)->addSeconds(30));

        expect(ProductPolicy::formattedRemainingPushCooldown())->toBe('30 detik');
    });

    it('formats hours, minutes, and seconds together', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        // nextPushAt = base + 4h = 14:00:00, advance by 30m45s → remaining = 3h 29m 15s
        Carbon::setTestNow($base->copy()->addMinutes(30)->addSeconds(45));

        expect(ProductPolicy::formattedRemainingPushCooldown())->toBe('3 jam 29 menit 15 detik');
    });
});

// ── nextPushAt / remainingPushCooldownSeconds ──────────────────────────────────

describe('nextPushAt', function () {
    it('returns null when user has never pushed', function () {
        $user = makeUser();
        $this->actingAs($user);

        expect(ProductPolicy::nextPushAt())->toBeNull();
    });

    it('returns stored pushed_at + 6 hours', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        expect(ProductPolicy::nextPushAt()->toDateTimeString())
            ->toBe(nextPushAtOf($product)->toDateTimeString());
    });

    it('uses the latest pushed_at across all lapak products', function () {
        $user = makeUser();
        $this->actingAs($user);

        $first = Carbon::create(2024, 6, 15, 8, 0, 0);
        Carbon::setTestNow($first);
        makeProduct($user->lapak);

        $second = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($second);
        $product2 = makeProduct($user->lapak);

        expect(ProductPolicy::nextPushAt()->toDateTimeString())
            ->toBe(nextPushAtOf($product2)->toDateTimeString());
    });

    it('applies 4h cooldown from creation when last product was created after last push', function () {
        $user = makeUser();
        $this->actingAs($user);

        // Older product
        $oldTime = Carbon::create(2024, 6, 15, 9, 0, 0);
        Carbon::setTestNow($oldTime);
        makeProduct($user->lapak);

        // Newer product created after old push
        $newTime = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($newTime);
        $newProduct = makeProduct($user->lapak);

        Carbon::setTestNow($newTime->copy()->addHours(3));

        // nextPushAt = newProduct.createdAt + 4h = newTime + 4h
        expect(ProductPolicy::nextPushAt()->toDateTimeString())
            ->toBe($newTime->copy()->addHours(4)->toDateTimeString());
    });
});

describe('remainingPushCooldownSeconds', function () {
    it('returns 0 when there is no push history', function () {
        $user = makeUser();
        $this->actingAs($user);

        expect(ProductPolicy::remainingPushCooldownSeconds())->toBe(0);
    });

    it('returns positive seconds while in cooldown', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        makeProduct($user->lapak);

        // nextPushAt = base + 4h, advance 1h → remaining = 3h
        Carbon::setTestNow($base->copy()->addHour());

        expect(ProductPolicy::remainingPushCooldownSeconds())->toBe(3 * 3600);
    });

    it('returns 0 when cooldown has expired', function () {
        $user = makeUser();
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        makeProduct($user->lapak);

        // nextPushAt = base + 4h, advance 5h → expired
        Carbon::setTestNow($base->copy()->addHours(5));

        expect(ProductPolicy::remainingPushCooldownSeconds())->toBe(0);
    });
});

// ── canPush ────────────────────────────────────────────────────────────────────

describe('canPush', function () {
    it('returns false when no user is authenticated', function () {
        expect(ProductPolicy::canPush())->toBeFalse();
    });

    it('returns false for admin', function () {
        $this->actingAs(makeUser(isAdmin: true));

        expect(ProductPolicy::canPush())->toBeFalse();
    });

    it('returns true when user has tokens and is not in cooldown', function () {
        $this->actingAs(makeUser(tokens: 5));

        // No products → no cooldown
        expect(ProductPolicy::canPush())->toBeTrue();
    });

    it('returns false when user is in cooldown', function () {
        $user = makeUser(tokens: 5);
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        makeProduct($user->lapak);

        Carbon::setTestNow($base->copy()->addHour()); // still in 4h cooldown

        expect(ProductPolicy::canPush())->toBeFalse();
    });

    it('returns false when user has no tokens', function () {
        $this->actingAs(makeUser(tokens: 0));

        expect(ProductPolicy::canPush())->toBeFalse();
    });
});

// ── currentPushTokens / hasSufficientPushToken ─────────────────────────────────

describe('currentPushTokens', function () {
    it('returns 0 when no user is authenticated', function () {
        expect(ProductPolicy::currentPushTokens())->toBe(0);
    });

    it('returns the authenticated user push_tokens', function () {
        $this->actingAs(makeUser(tokens: 7));

        expect(ProductPolicy::currentPushTokens())->toBe(7);
    });
});

describe('hasSufficientPushToken', function () {
    it('returns true when tokens >= 1', function () {
        $this->actingAs(makeUser(tokens: 1));

        expect(ProductPolicy::hasSufficientPushToken())->toBeTrue();
    });

    it('returns false when tokens = 0', function () {
        $this->actingAs(makeUser(tokens: 0));

        expect(ProductPolicy::hasSufficientPushToken())->toBeFalse();
    });
});

// ── formattedNextPushAt ────────────────────────────────────────────────────────

describe('formattedNextPushAt', function () {
    it('returns null when there is no push history', function () {
        $this->actingAs(makeUser());

        expect(ProductPolicy::formattedNextPushAt())->toBeNull();
    });

    it('returns "pukul H:i" when next push is today', function () {
        $user = makeUser();
        $this->actingAs($user);

        // Create at 10:00 → nextPushAt = 14:00 (same day)
        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        // Stay at base (14:00 is today relative to 10:00)
        $nextPush = nextPushAtOf($product);

        expect(ProductPolicy::formattedNextPushAt())->toBe('pukul ' . $nextPush->format('H:i'));
    });

    it('returns full date format when next push is on a different day', function () {
        $user = makeUser();
        $this->actingAs($user);

        // Create at 22:00 → pushed_at = 20:00 → nextPushAt = 02:00 next day
        $base = Carbon::create(2024, 6, 15, 22, 0, 0);
        Carbon::setTestNow($base);
        $product = makeProduct($user->lapak);

        $nextPush = nextPushAtOf($product); // will be next day

        expect(ProductPolicy::formattedNextPushAt())
            ->toBe($nextPush->format('d M Y') . ' pukul ' . $nextPush->format('H:i'));
    });
});

// ── blockedPushMessage ─────────────────────────────────────────────────────────

describe('blockedPushMessage', function () {
    it('shows can-push message with token count when push is available', function () {
        $this->actingAs(makeUser(tokens: 3));

        expect(ProductPolicy::blockedPushMessage())
            ->toContain('sudah bisa diangkat')
            ->toContain('3');
    });

    it('shows insufficient token message when tokens = 0', function () {
        $this->actingAs(makeUser(tokens: 0));

        expect(ProductPolicy::blockedPushMessage())
            ->toContain('Token tidak cukup');
    });

    it('shows cooldown message when in cooldown', function () {
        $user = makeUser(tokens: 5);
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        makeProduct($user->lapak);
        Carbon::setTestNow($base->copy()->addHour());

        expect(ProductPolicy::blockedPushMessage())
            ->toContain('bisa mengangkat produk lagi dalam');
    });
});

// ── pushTooltip ────────────────────────────────────────────────────────────────

describe('pushTooltip', function () {
    it('shows token count when push is available', function () {
        $this->actingAs(makeUser(tokens: 4));

        expect(ProductPolicy::pushTooltip())
            ->toContain('Angkat produk ke urutan teratas')
            ->toContain('4');
    });

    it('shows insufficient token message when tokens = 0', function () {
        $this->actingAs(makeUser(tokens: 0));

        expect(ProductPolicy::pushTooltip())
            ->toContain('Token tidak cukup');
    });

    it('returns generic tooltip when user is admin (no nextPushAt)', function () {
        $this->actingAs(makeUser(isAdmin: true));

        expect(ProductPolicy::pushTooltip())->toBe('Angkat produk ke urutan teratas');
    });

    it('shows next push time when user is in cooldown', function () {
        $user = makeUser(tokens: 5);
        $this->actingAs($user);

        $base = Carbon::create(2024, 6, 15, 10, 0, 0);
        Carbon::setTestNow($base);
        makeProduct($user->lapak);
        Carbon::setTestNow($base->copy()->addHour());

        expect(ProductPolicy::pushTooltip())
            ->toContain('Bisa angkat lagi pada');
    });
});

// ── CRUD policy methods ────────────────────────────────────────────────────────

describe('viewAny', function () {
    it('always returns true', function () {
        expect((new ProductPolicy())->viewAny(makeUser()))->toBeTrue();
    });
});

describe('create', function () {
    it('allows regular user to create', function () {
        expect((new ProductPolicy())->create(makeUser()))->toBeTrue();
    });

    it('denies admin from creating', function () {
        expect((new ProductPolicy())->create(makeUser(isAdmin: true)))->toBeFalse();
    });
});

describe('update', function () {
    it('allows owner to update', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        expect((new ProductPolicy())->update($user, $product))->toBeTrue();
    });

    it('denies non-owner from updating', function () {
        $owner   = makeUser();
        $other   = makeUser();
        $product = makeProduct($owner->lapak);

        expect((new ProductPolicy())->update($other, $product))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows owner to delete', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        expect((new ProductPolicy())->delete($user, $product))->toBeTrue();
    });

    it('denies non-owner from deleting', function () {
        $owner   = makeUser();
        $other   = makeUser();
        $product = makeProduct($owner->lapak);

        expect((new ProductPolicy())->delete($other, $product))->toBeFalse();
    });
});

describe('deleteAny', function () {
    it('allows regular user', function () {
        expect((new ProductPolicy())->deleteAny(makeUser()))->toBeTrue();
    });

    it('denies admin', function () {
        expect((new ProductPolicy())->deleteAny(makeUser(isAdmin: true)))->toBeFalse();
    });
});
