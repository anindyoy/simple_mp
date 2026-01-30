<?php

namespace App\Models;

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LapakProfile extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'lapak_id');
    }

    protected $appends = ['profile_image_url'];

    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image) {
            return Str::startsWith($this->profile_image, ['http://', 'https://'])
                ? $this->profile_image
                : asset('storage/' . $this->profile_image);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    public function getJoinedAtLabelAttribute(): string
    {
        return $this->created_at
            ? $this->created_at->translatedFormat('d F Y')
            : '-';
    }

    // =========================
    // WHATSAPP
    // =========================
    public function getWhatsappUrlAttribute(): ?string
    {
        if (!$this->whatsapp_number) {
            return null;
        }

        $number = preg_replace('/[^0-9]/', '', $this->whatsapp_number);

        if (str_starts_with($number, '08')) {
            $number = '628' . substr($number, 2);
        }

        $message = 'Halo, saya tertarik dengan produk di lapak *'
            . $this->name
            . '* yang saya lihat di Jual Beli Cimanglid.';

        return 'https://wa.me/' . $number . '?text=' . urlencode($message);
    }

    // =========================
    // TELEGRAM
    // =========================
    public function getTelegramUrlAttribute(): ?string
    {
        if (!$this->telegram_username) {
            return null;
        }

        $username = ltrim($this->telegram_username, '@');

        return 'https://t.me/' . $username;
    }
}
