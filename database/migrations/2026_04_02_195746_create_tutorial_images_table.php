<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tutorial_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutorial_page_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('image'); // path file
            $table->text('caption')->nullable(); // deskripsi gambar
            $table->integer('sort_order')->default(0); // urutan step
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutorial_images');
    }
};
