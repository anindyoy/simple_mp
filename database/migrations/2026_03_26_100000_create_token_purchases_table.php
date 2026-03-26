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
        Schema::create('token_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('quantity'); // Jumlah token yang dibeli
            $table->integer('total_price'); // Total harga dalam mata uang lokal (IDR)
            $table->string('status')->default('pending'); // pending, confirmed, cancelled
            $table->string('payment_method')->nullable(); // bank_transfer, etc
            $table->string('bank_account')->nullable(); // Rekening tujuan yang dipilih
            $table->string('proof_of_payment')->nullable(); // Path to uploaded proof
            $table->text('notes')->nullable(); // Catatan dari user atau admin
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Index untuk query cepat
            $table->index(['user_id', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_purchases');
    }
};
