<?php

namespace App\Models;

use App\Models\User;
use App\Models\Report;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\ProductModeration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LapakProfile extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($lapak) {
            dd('creating triggered');
            $lapak->slug = static::generateUniqueSlug($lapak->name);
        });

        static::updating(function ($lapak) {
            if ($lapak->isDirty('name')) {
                $lapak->slug = static::generateUniqueSlug($lapak->name);
            }
        });
    }

    protected static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'lapak_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
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

    public function moderations()
    {
        return $this->hasMany(ProductModeration::class, 'lapak_profile_id');
    }

    public function latestDeactivation()
    {
        return $this->moderations()
            ->where('type', ProductModeration::TYPE_DEACTIVATION)
            ->latest()
            ->first();
    }

    public function pendingReactivationRequest()
    {
        return $this->moderations()
            ->where('type', ProductModeration::TYPE_REACTIVATION)
            ->where('status', ProductModeration::STATUS_PENDING)
            ->latest()
            ->first();
    }
}
