<?php

namespace App\Models;

use App\Models\Report;
use App\Models\Category;
use App\Models\ProductModeration;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Mengubah string pushed_at menjadi objek Carbon (Waktu) secara otomatis
    protected $casts = [
        'pushed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        /**
         * Generate slug saat create
         */
        static::creating(function ($product) {
            $product->slug = Str::slug($product->title) . '-' . rand(1000, 9999);
        });

        /**
         * Validasi condition berdasarkan kategori
         */
        static::saving(function ($product) {
            if (
                $product->category?->supportsCondition()
                && is_null($product->condition)
            ) {
                throw new \InvalidArgumentException(
                    'Condition wajib diisi untuk kategori ini.'
                );
            }
        });

        /**
         * Pastikan gambar pertama menjadi gambar utama
         * Dieksekusi SETELAH product tersimpan
         */
        static::saved(function ($product) {
            $primaryImage = $product->images()
                ->orderBy('id')
                ->first();

            if ($primaryImage) {
                $product->images()
                    ->update([
                        'is_primary' => false,
                    ]);

                $primaryImage->update([
                    'is_primary' => true,
                ]);
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function lapak(): BelongsTo
    {
        return $this->belongsTo(LapakProfile::class, 'lapak_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function moderations(): HasMany
    {
        return $this->hasMany(ProductModeration::class);
    }

    public function latestDeactivation(): HasOne
    {
        return $this->hasOne(ProductModeration::class)
            ->where('type', ProductModeration::TYPE_DEACTIVATION)
            ->where('status', ProductModeration::STATUS_APPROVED)
            ->latestOfMany();
    }

    public function latestReactivationRequest(): HasOne
    {
        return $this->hasOne(ProductModeration::class)
            ->where('type', ProductModeration::TYPE_REACTIVATION)
            ->latestOfMany();
    }

    public function pendingReactivationRequest(): HasOne
    {
        return $this->hasOne(ProductModeration::class)
            ->where('type', ProductModeration::TYPE_REACTIVATION)
            ->where('status', ProductModeration::STATUS_PENDING)
            ->latestOfMany();
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    // Helper untuk cek apakah sudah boleh push (6 jam)
    public function canBePushed(): bool
    {
        return $this->pushed_at->diffInHours(now()) >= 6;
    }

    public function hasCondition(): bool
    {
        return !is_null($this->condition)
            && $this->category?->supportsCondition();
    }

    public function conditionLabel(): ?string
    {
        return match ($this->condition) {
            'baru' => 'Baru',
            'seken' => 'Bekas',
            default => null,
        };
    }
}
