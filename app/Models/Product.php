<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
         * Pastikan hanya 1 gambar primary per produk
         * Dieksekusi SETELAH product tersimpan
         */
        static::saved(function ($product) {
            // Ambil 1 gambar primary (yang paling akhir diupdate)
            $primaryImage = $product->images()
                ->where('is_primary', true)
                ->orderByDesc('updated_at')
                ->first();

            if ($primaryImage) {
                // Set gambar lain menjadi non-primary
                $product->images()
                    ->where('id', '!=', $primaryImage->id)
                    ->update(['is_primary' => false]);
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
