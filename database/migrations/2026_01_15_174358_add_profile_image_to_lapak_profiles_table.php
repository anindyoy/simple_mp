<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->string('profile_image')
                ->nullable()
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('lapak_profiles', function (Blueprint $table) {
            $table->dropColumn('profile_image');
        });
    }
};
