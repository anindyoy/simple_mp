<?php

namespace App\Services;

use App\Models\User;
use App\Models\Report;
use App\Models\Product;
use App\Models\LapakProfile;
use App\Models\ProductModeration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductModerationService
{
    public function requestReactivation(
        Product|LapakProfile $target,
        User $user,
        string $reason
    ): ProductModeration {

        // cek pending generic
        $hasPending = $target->moderations()
            ->where('type', ProductModeration::TYPE_REACTIVATION)
            ->where('status', ProductModeration::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            throw new \DomainException('Masih ada pengajuan aktivasi yang pending.');
        }

        return DB::transaction(function () use ($target, $user, $reason) {

            $data = [
                'type' => ProductModeration::TYPE_REACTIVATION,
                'status' => ProductModeration::STATUS_PENDING,
                'reason' => 'permohonan_aktivasi_ulang',
                'description' => $reason,
                'requested_by_user_id' => $user->id,
            ];

            if ($target instanceof Product) {
                $data['product_id'] = $target->id;
            }

            if ($target instanceof LapakProfile) {
                $data['lapak_profile_id'] = $target->id;
            }

            return ProductModeration::create($data);
        });
    }

    public function approveReactivation(
        Product|LapakProfile $target,
        User $admin
    ): ProductModeration {

        $pending = $this->getPendingReactivation($target);

        return DB::transaction(function () use ($target, $admin, $pending) {

            $pending->update([
                'status' => ProductModeration::STATUS_APPROVED,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $target->update([
                'is_active' => true,
            ]);

            return $pending;
        });
    }

    public function rejectReactivation(
        Product|LapakProfile $target,
        User $admin,
        string $reason
    ): ProductModeration {

        $pending = $this->getPendingReactivation($target);

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

    protected function getPendingReactivation(
        Product|LapakProfile $target
    ): ProductModeration {

        $pending = $target->moderations()
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

    public function deactivateTarget(
        Product|LapakProfile $target,
        User $admin,
        string $reason,
        ?string $description = null,
        ?Report $latestReport = null,
    ): ProductModeration {

        return DB::transaction(function () use (
            $target,
            $admin,
            $reason,
            $description,
            $latestReport
        ) {

            $moderationData = [
                'type' => ProductModeration::TYPE_DEACTIVATION,
                'status' => ProductModeration::STATUS_APPROVED,
                'reason' => $reason,
                'description' => $description,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
                'report_id' => $latestReport?->id,
            ];

            if ($target instanceof Product) {
                $moderationData['product_id'] = $target->id;
            }

            if ($target instanceof LapakProfile) {
                $moderationData['lapak_profile_id'] = $target->id;
            }

            $moderation = ProductModeration::create($moderationData);

            $target->update([
                'is_active' => false,
            ]);

            // update semua report pending jadi reviewed
            Report::query()
                ->where('reportable_type', $target::class)
                ->where('reportable_id', $target->id)
                ->where('status', 'pending')
                ->update(['status' => 'reviewed']);

            return $moderation;
        });
    }
}
