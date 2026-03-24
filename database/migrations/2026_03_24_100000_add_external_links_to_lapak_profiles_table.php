<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->json('external_links')->nullable()->after('telegram_username');
        });
    }

    public function down(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->dropColumn('external_links');
        });
    }
};
