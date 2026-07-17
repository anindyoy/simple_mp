<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['product_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};