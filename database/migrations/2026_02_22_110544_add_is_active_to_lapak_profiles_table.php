<?php

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
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->boolean('is_active')
                ->default(true)
                ->after('longitude')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
