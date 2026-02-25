<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductModeration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductModerationService
{
    public function requestReactivation(Product $product, User $user, string $reason): ProductModeration
    {
        if ($product->pendingReactivationRequest) {
            throw new \DomainException('Masih ada pengajuan aktivasi yang pending.');
        }

        return DB::transaction(function () use ($product, $user, $reason) {

            return ProductModeration::create([
                'product_id' => $product->id,
                'type' => ProductModeration::TYPE_REACTIVATION,
                'status' => ProductModeration::STATUS_PENDING,
                'reason' => 'permohonan_aktivasi_ulang',
                'description' => $reason,
                'requested_by_user_id' => $user->id,
            ]);
        });
    }

    public function approveReactivation(Product $product, User $admin): ProductModeration
    {
        $pending = $this->getPendingReactivation($product);

        return DB::transaction(function () use ($product, $admin, $pending) {

            $pending->update([
                'status' => ProductModeration::STATUS_APPROVED,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $product->update([
                'is_active' => true,
            ]);

            return $pending;
        });
    }

    public function rejectReactivation(Product $product, User $admin, string $reason): ProductModeration
    {
        $pending = $this->getPendingReactivation($product);

        return DB::transaction(function () use ($admin, $pending, $reason) {

            $pending->update([
                'status' => ProductModeration::STATUS_REJECTED,
                'description' => $reason,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $pending;
        });
    }

    protected function getPendingReactivation(Product $product): ProductModeration
    {
        $pending = $product->moderations()
            ->where('type', ProductModeration::TYPE_REACTIVATION)
            ->where('status', ProductModeration::STATUS_PENDING)
            ->latest()
            ->first();

        if (! $pending) {
            throw new \DomainException('Tidak ada pengajuan aktivasi yang pending.');
        }

        return $pending;
    }

    public function notifyAdmins(Product $product): Collection
    {
        return User::query()
            ->where('is_admin', true)
            ->whereKeyNot(auth()->id())
            ->get();
    }
}