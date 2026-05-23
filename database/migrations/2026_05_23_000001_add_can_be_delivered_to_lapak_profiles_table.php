<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->boolean('can_be_delivered')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->dropColumn('can_be_delivered');
        });
    }
};
