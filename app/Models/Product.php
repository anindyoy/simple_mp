<?php

namespace App\Models;

use App\Models\Report;
use App\Models\Category;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use App\Models\LapakProfile;
use App\Models\ProductModeration;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\ProductScheduleService;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory, LogsActivity;
    protected $guarded = [];

    // Mengubah string pushed_at menjadi objek Carbon (Waktu) secara otomatis
    protected $casts = [
        'pushed_at' => 'datetime',
        'is_active' => 'boolean',
        'price' => 'integer',

    ];

    protected static function boot()
    {
        parent::boot();

        /**
         * Generate slug saat create
         */
        static::creating(function ($product) {
            $product->slug = Str::slug($product->title) . '-' . rand(1000, 9999);

            // Set lapak_id dari auth user hanya jika belum ada
            if (is_null($product->lapak_id) && auth()->check()) {
                $product->lapak_id = auth()->user()?->lapak?->id;
            }

            $product->pushed_at = now()->subHours(2);
        });

        static::created(function ($product) {
            ProductScheduleService::append($product);
        });

        static::deleted(function ($product) {
            ProductScheduleService::rebuild($product->lapak_id);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'price', 'is_active', 'condition'])
            ->useLogName('product');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->quality(70)
            ->nonQueued();

        $this
            ->addMediaConversion('webp')
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }

    public function getThumbnailUrlAttribute(): string
    {
        $url = $this->getFirstMediaUrl('products', 'thumb');
        return $url ?: asset('img/default-lapak-image.png');
    }

    public function addRandomImage(): void
    {
        $seedDir = storage_path('app/seed-samples');
        $files = collect(scandir($seedDir))
            ->reject(fn($f) => in_array($f, ['.', '..']))
            ->values();

        if ($files->isEmpty()) {
            logger()->error('NO FILES FOUND');
            return;
        }

        $file = $files->random();

        $fullPath = $seedDir . '/' . $file;

        try {
            $this
                ->addMedia($fullPath)
                ->preservingOriginal()
                ->toMediaCollection('products');
        } catch (\Throwable $e) {
            logger()->error('MEDIA FAILED', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
