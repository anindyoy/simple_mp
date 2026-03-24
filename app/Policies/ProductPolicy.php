<?php

namespace App\Policies;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    private const PUSH_COOLDOWN_HOURS = 6;

    private function ownsProduct(User $user, Product $product): bool
    {
        return (int) $product->lapak?->user_id === (int) $user->id;
    }

    private static function latestPushAt(): ?Carbon
    {
        $user = auth()->user();

        if (! $user || $user->is_admin || ! $user->lapak) {
            return null;
        }

        $lastPush = Product::where('lapak_id', $user->lapak->id)
            ->whereNotNull('pushed_at')
            ->max('pushed_at');

        if (! $lastPush) {
            return null;
        }

        return Carbon::parse($lastPush);
    }

    public static function nextPushAt(): ?Carbon
    {
        $lastPushAt = self::latestPushAt();

        return $lastPushAt?->copy()->addHours(self::PUSH_COOLDOWN_HOURS);
    }

    public static function remainingPushCooldownSeconds(): int
    {
        $nextPushAt = self::nextPushAt();

        if (! $nextPushAt) {
            return 0;
        }

        return max(0, now()->diffInSeconds($nextPushAt, false));
    }

    public static function formattedRemainingPushCooldown(): string
    {
        $seconds = self::remainingPushCooldownSeconds();

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }

        if ($minutes > 0) {
            $parts[] = $minutes . ' menit';
        }

        if ($secs > 0 || $parts === []) {
            $parts[] = $secs . ' detik';
        }

        return implode(' ', $parts);
    }

    public static function blockedPushMessage(): string
    {
        if (self::canPush()) {
            return 'Produk sudah bisa diangkat sekarang.';
        }

        return 'Kamu bisa mengangkat produk lagi dalam ' . self::formattedRemainingPushCooldown() . '.';
    }

    public static function formattedNextPushAt(): ?string
    {
        $nextPush = self::nextPushAt();

        if (! $nextPush) {
            return null;
        }

        return $nextPush->isToday()
            ? 'pukul ' . $nextPush->format('H:i')
            : $nextPush->format('d M Y') . ' pukul ' . $nextPush->format('H:i');
    }

    /**
     * Boleh lihat daftar produk
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public static function canPush(): bool
    {
        $user = auth()->user();

        if (! $user || $user->is_admin) {
            return false; // admin tidak boleh push
        }

        return self::remainingPushCooldownSeconds() === 0;
    }

    public static function pushTooltip(): string
    {
        if (self::canPush()) {
            return 'Angkat produk ke urutan teratas';
        }

        $formattedNextPushAt = self::formattedNextPushAt();

        if (! $formattedNextPushAt) {
            return 'Angkat produk ke urutan teratas';
        }

        return 'Bisa angkat lagi pada ' . $formattedNextPushAt;
    }

    /**
     * Boleh lihat detail produk
     */
    public function view(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * ❌ Admin tidak boleh menambah produk
     */
    public function create(User $user): bool
    {
        return ! $user->is_admin;
    }

    /**
     * ❌ Admin tidak boleh mengubah produk
     */
    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * ❌ Admin tidak boleh menghapus produk
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * Optional: bulk delete
     */
    public function deleteAny(User $user): bool
    {
        return ! $user->is_admin;
    }
}
