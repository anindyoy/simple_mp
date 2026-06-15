<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Report;
use App\Models\Category;
use App\Models\LapakProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper: create a product with its required relationships.
 */
function createReportableProduct(): Product
{
    $category = Category::factory()->create();
    $lapak = LapakProfile::factory()->create();

    return Product::factory()->withoutImages()->create([
        'category_id' => $category->id,
        'lapak_id' => $lapak->id,
        'is_active' => true,
    ]);
}

/**
 * Helper: create a lapak profile.
 */
function createReportableLapak(): LapakProfile
{
    return LapakProfile::factory()->create();
}

describe('ReportController@store', function () {
    // ── Validation ────────────────────────────────────────────────────────

    it('validasi reportable_type required', function () {
        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_id' => 1,
                'reason' => 'spam',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reportable_type');
    });

    it('validasi reportable_type harus product atau lapak', function () {
        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'user',
                'reportable_id' => 1,
                'reason' => 'spam',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reportable_type');
    });

    it('validasi reportable_id required', function () {
        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reason' => 'spam',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reportable_id');
    });

    it('validasi reason required', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reason');
    });

    it('validasi reason max 255 karakter', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => str_repeat('a', 256),
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reason');
    });

    it('validasi description max 2000 karakter', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'spam',
                'description' => str_repeat('a', 2001),
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('description');
    });

    it('validasi reporter_name max 100 karakter', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'spam',
                'reporter_name' => str_repeat('a', 101),
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reporter_name');
    });

    it('validasi reporter_email format email', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'spam',
                'reporter_email' => 'invalid-email',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('reporter_email');
    });

    // ── Guest User Tests ──────────────────────────────────────────────────

    it('guest dapat melaporkan product dengan data valid', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'produk_terlarang',
                'description' => 'Ini barang ilegal',
                'reporter_name' => 'Budi',
                'reporter_email' => 'budi@example.com',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success');

        expect(Report::count())->toBe(1);

        $report = Report::first();
        expect($report->reportable_type)->toBe(Product::class);
        expect($report->reportable_id)->toBe($product->id);
        expect($report->reason)->toBe('produk_terlarang');
        expect($report->description)->toBe('Ini barang ilegal');
        expect($report->reporter_name)->toBe('Budi');
        expect($report->reporter_email)->toBe('budi@example.com');
        expect($report->user_id)->toBeNull();
        expect($report->status)->toBe('pending');
    });

    it('guest dapat melaporkan lapak dengan data valid', function () {
        $lapak = createReportableLapak();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'lapak',
                'reportable_id' => $lapak->id,
                'reason' => 'konten_tidak_pantas',
                'description' => 'Konten tidak sesuai',
                'reporter_name' => 'Ani',
                'reporter_email' => 'ani@example.com',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success');

        expect(Report::count())->toBe(1);

        $report = Report::first();
        expect($report->reportable_type)->toBe(LapakProfile::class);
        expect($report->reportable_id)->toBe($lapak->id);
        expect($report->reason)->toBe('konten_tidak_pantas');
        expect($report->description)->toBe('Konten tidak sesuai');
        expect($report->reporter_name)->toBe('Ani');
        expect($report->reporter_email)->toBe('ani@example.com');
        expect($report->user_id)->toBeNull();
    });

    it('guest dapat melaporkan tanpa description', function () {
        $product = createReportableProduct();

        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'spam',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $report = Report::first();
        expect($report->description)->toBeNull();
        expect($report->reporter_name)->toBeNull();
        expect($report->reporter_email)->toBeNull();
    });

    // ── Authenticated User Tests ──────────────────────────────────────────

    it('user yang login dapat melaporkan product dan user_id tercatat', function () {
        $user = User::factory()->create();
        $product = createReportableProduct();

        $response = $this
            ->actingAs($user)
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'penipuan',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $report = Report::first();
        expect($report->user_id)->toBe($user->id);
    });

    it('user yang login dapat melaporkan lapak', function () {
        $user = User::factory()->create();
        $lapak = createReportableLapak();

        $response = $this
            ->actingAs($user)
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'lapak',
                'reportable_id' => $lapak->id,
                'reason' => 'spam',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success');

        expect(Report::count())->toBe(1);
    });

    // ── Duplicate Report Prevention ───────────────────────────────────────

    it('user yang login tidak bisa melaporkan item yang sama dua kali', function () {
        $user = User::factory()->create();
        $product = createReportableProduct();

        // First report - should succeed
        $this
            ->actingAs($user)
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'spam',
            ]);

        expect(Report::count())->toBe(1);

        // Second report on same item - should fail
        $response = $this
            ->actingAs($user)
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'penipuan',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrorsIn('report', 'duplicate');

        // Still only 1 report
        expect(Report::count())->toBe(1);
    });

    it('guest juga terdeteksi duplicate karena auth()->id() null sama', function () {
        $product = createReportableProduct();

        // First report by guest
        $this
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'spam',
            ]);

        expect(Report::count())->toBe(1);

        // Second report by same guest - blocked by duplicate check
        // because auth()->id() = null matches the first report's user_id = null
        $response = $this
            ->from('/')
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => $product->id,
                'reason' => 'penipuan',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrorsIn('report', 'duplicate');

        expect(Report::count())->toBe(1);
    });

    // ── Non-existent reportable ────────────────────────────────────────────

    it('mengembalikan 404 jika reportable_id tidak ditemukan', function () {
        $this
            ->post(route('report.store'), [
                'reportable_type' => 'product',
                'reportable_id' => 99999,
                'reason' => 'spam',
            ])
            ->assertNotFound();
    });

    it('mengembalikan 404 jika reportable_id lapak tidak ditemukan', function () {
        $this
            ->post(route('report.store'), [
                'reportable_type' => 'lapak',
                'reportable_id' => 99999,
                'reason' => 'spam',
            ])
            ->assertNotFound();
    });
});