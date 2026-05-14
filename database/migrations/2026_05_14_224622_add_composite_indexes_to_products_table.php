<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Untuk filter utama: WHERE is_active = 1 ORDER BY pushed_at DESC
            $table->index(['is_active', 'pushed_at'], 'products_is_active_pushed_at_index');

            // Untuk filter: WHERE is_active = 1 AND category_id = ?
            $table->index(['is_active', 'category_id'], 'products_is_active_category_id_index');

            // Untuk filter: WHERE is_active = 1 AND condition = ?
            $table->index(['is_active', 'condition'], 'products_is_active_condition_index');

            // Untuk whereHas lapak + filter gabungan
            $table->index(['lapak_id', 'is_active'], 'products_lapak_id_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_pushed_at_index');
            $table->dropIndex('products_is_active_category_id_index');
            $table->dropIndex('products_is_active_condition_index');
            $table->dropIndex('products_lapak_id_is_active_index');
        });
    }
};
