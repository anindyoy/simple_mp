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
        Schema::create('product_moderations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lapak_profile_id')
                ->nullable()
                ->constrained('lapak_profiles')
                ->nullOnDelete();

            $table->foreignId('report_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type');   // deactivation | reactivation
            $table->string('status'); // pending | approved | rejected
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // 👇 beri nama index manual (lebih pendek dari 64 karakter)
            $table->index(
                ['product_id', 'lapak_profile_id', 'type', 'status'],
                'pm_product_lapak_type_status_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_moderations');
    }
};
