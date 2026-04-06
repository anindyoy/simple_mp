<?php

namespace App\Policies;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    private const PUSH_COOLDOWN_HOURS = 6;
    private const PUSH_COOLDOWN_AFTER_CREATE = 4;
    private const PUSH_TOKEN_COST = 1;

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

        $lastProduct = Product::where('lapak_id', $user->lapak->id)
            ->latest('created_at')
            ->first();

        $lastPush = Product::where('lapak_id', $user->lapak->id)
            ->whereNotNull('pushed_at')
            ->max('pushed_at');

        if (! $lastPush) {
            return null;
        }

        $lastPushAt = Carbon::parse($lastPush);

        // jika produk terakhir baru dibuat → pakai rule 4 jam
        if ($lastProduct && $lastProduct->created_at->gt($lastPushAt)) {
            return $lastProduct->created_at->copy()
                ->addHours(self::PUSH_COOLDOWN_AFTER_CREATE)
                ->subHours(self::PUSH_COOLDOWN_HOURS);
        }

        return $lastPushAt;
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
            return 'Produk sudah bisa diangkat sekarang. Token tersisa: ' . self::currentPushTokens() . '.';
        }

        if (! self::hasSufficientPushToken()) {
            return 'Token tidak cukup untuk mengangkat produk. Kamu butuh ' . self::PUSH_TOKEN_COST . ' token (sisa: ' . self::currentPushTokens() . ').';
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

        return self::hasSufficientPushToken()
            && self::remainingPushCooldownSeconds() === 0;
    }

    public static function pushTooltip(): string
    {
        if (self::canPush()) {
            return 'Angkat produk ke urutan teratas (token tersisa: ' . self::currentPushTokens() . ')';
        }

        if (! self::hasSufficientPushToken()) {
            return 'Token tidak cukup untuk mengangkat produk (butuh ' . self::PUSH_TOKEN_COST . ').';
        }

        $formattedNextPushAt = self::formattedNextPushAt();

        if (! $formattedNextPushAt) {
            return 'Angkat produk ke urutan teratas';
        }

        return 'Bisa angkat lagi pada ' . $formattedNextPushAt;
    }

    public static function currentPushTokens(): int
    {
        $user = auth()->user();

        if (! $user) {
            return 0;
        }

        return max(0, (int) ($user->push_tokens ?? 0));
    }

    public static function hasSufficientPushToken(): bool
    {
        return self::currentPushTokens() >= self::PUSH_TOKEN_COST;
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
