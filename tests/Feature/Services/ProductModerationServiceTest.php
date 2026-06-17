<?php

use App\Models\ProductModeration;
use App\Models\User;
use App\Services\ProductModerationService;

// ── requestReactivation ────────────────────────────────────────────────────────

describe('requestReactivation', function () {
    it('creates a pending reactivation moderation for a product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        $moderation = $service->requestReactivation($product, $user, 'Produk saya sudah diperbaiki');

        expect($moderation)
            ->type->toBe(ProductModeration::TYPE_REACTIVATION)
            ->status->toBe(ProductModeration::STATUS_PENDING)
            ->reason->toBe('permohonan_aktivasi_ulang')
            ->description->toBe('Produk saya sudah diperbaiki')
            ->product_id->toBe($product->id)
            ->lapak_profile_id->toBeNull()
            ->requested_by_user_id->toBe($user->id);
    });

    it('creates a pending reactivation moderation for a lapak profile', function () {
        $user    = makeUser();
        $lapak   = $user->lapak;
        $service = new ProductModerationService();

        $moderation = $service->requestReactivation($lapak, $user, 'Lapak sudah bersih');

        expect($moderation)
            ->type->toBe(ProductModeration::TYPE_REACTIVATION)
            ->status->toBe(ProductModeration::STATUS_PENDING)
            ->lapak_profile_id->toBe($lapak->id)
            ->product_id->toBeNull()
            ->requested_by_user_id->toBe($user->id);
    });

    it('persists the moderation record to the database', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        $moderation = $service->requestReactivation($product, $user, 'Alasan');

        expect(ProductModeration::find($moderation->id))->not->toBeNull();
    });

    it('throws DomainException when a pending reactivation already exists for a product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);

        expect(fn () => $service->requestReactivation($product, $user, 'Coba lagi'))
            ->toThrow(\DomainException::class, 'Masih ada pengajuan aktivasi yang pending.');
    });

    it('throws DomainException when a pending reactivation already exists for a lapak', function () {
        $user    = makeUser();
        $lapak   = $user->lapak;
        $service = new ProductModerationService();

        ProductModeration::factory()->create([
            'lapak_profile_id'     => $lapak->id,
            'product_id'           => null,
            'type'                 => ProductModeration::TYPE_REACTIVATION,
            'status'               => ProductModeration::STATUS_PENDING,
            'reviewed_by_user_id'  => null,
            'reviewed_at'          => null,
        ]);

        expect(fn () => $service->requestReactivation($lapak, $user, 'Coba lagi'))
            ->toThrow(\DomainException::class);
    });
});

// ── approveReactivation ────────────────────────────────────────────────────────

describe('approveReactivation', function () {
    it('marks the moderation as approved', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);

        $moderation = $service->approveReactivation($product, $admin);

        expect($moderation->fresh())
            ->status->toBe(ProductModeration::STATUS_APPROVED);
    });

    it('records the reviewer and reviewed_at timestamp', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);

        $moderation = $service->approveReactivation($product, $admin);

        expect($moderation->fresh())
            ->reviewed_by_user_id->toBe($admin->id)
            ->reviewed_at->not->toBeNull();
    });

    it('activates the product', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);
        $service->approveReactivation($product, $admin);

        expect($product->fresh()->is_active)->toBeTrue();
    });

    it('activates the lapak profile', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $lapak   = $user->lapak;
        $service = new ProductModerationService();

        ProductModeration::factory()->create([
            'lapak_profile_id'    => $lapak->id,
            'product_id'          => null,
            'type'                => ProductModeration::TYPE_REACTIVATION,
            'status'              => ProductModeration::STATUS_PENDING,
            'reviewed_by_user_id' => null,
            'reviewed_at'         => null,
        ]);

        $service->approveReactivation($lapak, $admin);

        expect($lapak->fresh()->is_active)->toBeTruthy();
    });

    it('throws DomainException when no pending reactivation exists', function () {
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct(makeUser()->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        expect(fn () => $service->approveReactivation($product, $admin))
            ->toThrow(\DomainException::class, 'Tidak ada pengajuan aktivasi yang pending.');
    });
});

// ── rejectReactivation ────────────────────────────────────────────────────────

describe('rejectReactivation', function () {
    it('marks the moderation as rejected', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);

        $moderation = $service->rejectReactivation($product, $admin, 'Alasan penolakan');

        expect($moderation->fresh())
            ->status->toBe(ProductModeration::STATUS_REJECTED);
    });

    it('records the rejection reason, reviewer, and reviewed_at', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);

        $moderation = $service->rejectReactivation($product, $admin, 'Alasan penolakan');

        expect($moderation->fresh())
            ->description->toBe('Alasan penolakan')
            ->reviewed_by_user_id->toBe($admin->id)
            ->reviewed_at->not->toBeNull();
    });

    it('does not reactivate the product', function () {
        $user    = makeUser();
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($user->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        makePendingModeration($product);
        $service->rejectReactivation($product, $admin, 'Tidak memenuhi syarat');

        expect($product->fresh()->is_active)->toBeFalse();
    });

    it('throws DomainException when no pending reactivation exists', function () {
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct(makeUser()->lapak, ['is_active' => false]);
        $service = new ProductModerationService();

        expect(fn () => $service->rejectReactivation($product, $admin, 'Alasan'))
            ->toThrow(\DomainException::class, 'Tidak ada pengajuan aktivasi yang pending.');
    });
});

// ── notifyAdmins ──────────────────────────────────────────────────────────────

describe('notifyAdmins', function () {
    it('returns all admin users', function () {
        $admin1  = makeUser(isAdmin: true);
        $admin2  = makeUser(isAdmin: true);
        $regular = makeUser();
        $product = makeProduct($regular->lapak);
        $service = new ProductModerationService();

        $this->actingAs($regular);

        $result = $service->notifyAdmins($product);

        expect($result->pluck('id'))
            ->toContain($admin1->id)
            ->toContain($admin2->id)
            ->not->toContain($regular->id);
    });

    it('excludes the currently authenticated user', function () {
        $admin1 = makeUser(isAdmin: true);
        $admin2 = makeUser(isAdmin: true);
        $product = makeProduct($admin1->lapak);
        $service = new ProductModerationService();

        $this->actingAs($admin1); // admin1 is auth'd user

        $result = $service->notifyAdmins($product);

        expect($result->pluck('id'))
            ->toContain($admin2->id)
            ->not->toContain($admin1->id);
    });

    it('returns an empty collection when no other admin exists', function () {
        $admin   = makeUser(isAdmin: true);
        $product = makeProduct($admin->lapak);
        $service = new ProductModerationService();

        $this->actingAs($admin);

        expect($service->notifyAdmins($product))->toBeEmpty();
    });
});
