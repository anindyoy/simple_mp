<?php

namespace App\Policies;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    private function ownsProduct(User $user, Product $product): bool
    {
        return (int) $product->lapak?->user_id === (int) $user->id;
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

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return false; // admin tidak boleh push
        }

        $lastPush = Product::where('lapak_id', $user->lapak->id)
            ->whereNotNull('pushed_at')
            ->max('pushed_at');

        if (! $lastPush) {
            return true;
        }

        return Carbon::parse($lastPush)->addHours(6)->isPast();
    }

    public static function pushTooltip(): string
    {
        $user = auth()->user();

        $lastPush = Product::where('lapak_id', $user->lapak->id)
            ->whereNotNull('pushed_at')
            ->max('pushed_at');

        if (! $lastPush) {
            return 'Push produk ke atas';
        }

        $nextPush = Carbon::parse($lastPush)->addHours(6);

        return $nextPush->isFuture()
            ? 'Bisa push lagi pada ' . $nextPush->format('d M Y H:i')
            : 'Push produk ke atas';
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
