<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return Cache::remember(
            "setting.{$key}",
            now()->addHours(6),
            fn() => static::query()->where('key', $key)->value('value')
        ) ?? $default;
    }

    // Invalidate otomatis saat setting diubah
    protected static function booted(): void
    {
        static::saved(function ($setting) {
            Cache::forget("setting.{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting.{$setting->key}");
        });
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getIntValue(string $key, int $default = 0, int $min = 0): int
    {
        return max($min, (int) static::getValue($key, (string) $default));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value'])
            ->useLogName('setting');
    }
}
