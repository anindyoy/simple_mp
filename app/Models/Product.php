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
        static::creating(function ($product) {
            $product->slug = Str::slug($product->title) . '-' . rand(1000, 9999);
        });

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
