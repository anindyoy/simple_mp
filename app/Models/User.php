<?php

namespace App\Models;

use Filament\Panel;
use App\Models\LapakProfile;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'push_tokens' => 'integer',
        ];
    }

    public function lapak()
    {
        return $this->hasOne(LapakProfile::class);
    }

    /**
     * Get all token purchases for this user
     */
    public function tokenPurchases()
    {
        return $this->hasMany(TokenPurchase::class);
    }

    /**
     * Get confirmed token purchases history
     */
    public function confirmedTokenPurchases()
    {
        return $this->tokenPurchases()
            ->where('status', 'confirmed')
            ->orderBy('confirmed_at', 'desc');
    }

    /**
     * Add tokens to user balance
     */
    public function addTokens(int $quantity): void
    {
        $this->increment('push_tokens', $quantity);
    }

    /**
     * Deduct tokens from user balance
     */
    public function deductTokens(int $quantity): bool
    {
        if ($this->push_tokens >= $quantity) {
            $this->decrement('push_tokens', $quantity);
            return true;
        }
        return false;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail();
    }
}
