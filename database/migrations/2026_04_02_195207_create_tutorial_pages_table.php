<?php

use App\Models\TutorialPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tutorial_pages', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique(); // contoh: /admin/products
            $table->string('title')->nullable(); // judul tutorial
            $table->timestamps();
        });

        // 🔥 Insert default data
        TutorialPage::insert([
            [
                'url' => '/admin',
                'title' => 'Dashboard',
            ],
            [
                'url' => '/admin/buy-tokens-page',
                'title' => 'Beli Token',
            ],
            [
                'url' => '/admin/lapak-profile',
                'title' => 'Profil Lapak',
            ],
            [
                'url' => '/admin/products',
                'title' => 'Daftar Produk',
            ],
            [
                'url' => '/admin/products/create',
                'title' => 'Tambah Produk',
            ],
            [
                'url' => '/admin/token-purchases',
                'title' => 'Pembelian Token',
            ],

            // optional wildcard (recommended)
            [
                'url' => '/admin/products/*/edit',
                'title' => 'Edit Produk',
            ],
            [
                'url' => '/admin/token-purchases/*',
                'title' => 'Detail Pembelian Token',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutorial_pages');
    }
};
