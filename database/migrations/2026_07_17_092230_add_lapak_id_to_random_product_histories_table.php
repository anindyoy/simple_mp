<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('random_product_histories', function (Blueprint $table) {
            $table->foreignId('lapak_id')->nullable()->after('product_id')
                ->constrained('lapak_profiles')->cascadeOnDelete();
        });

        // Backfill lapak_id for existing rows from their related product.
        DB::table('random_product_histories')
            ->whereNull('lapak_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $lapakId = DB::table('products')->where('id', $row->product_id)->value('lapak_id');

                    if ($lapakId) {
                        DB::table('random_product_histories')->where('id', $row->id)->update(['lapak_id' => $lapakId]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('random_product_histories', function (Blueprint $table) {
            $table->dropForeign(['lapak_id']);
            $table->dropColumn('lapak_id');
        });
    }
};
