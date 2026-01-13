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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lapak_id')->constrained('lapak_profiles')->onDelete('cascade');
            $table->foreignId('category_id')->constrained();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('pushed_at')->useCurrent(); // Untuk urutan beranda
            $table->timestamps();

            // Index untuk mempercepat ORDER BY pushed_at DESC
            $table->index('pushed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
