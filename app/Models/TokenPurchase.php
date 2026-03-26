<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TokenPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quantity',
        'total_price',
        'status',
        'payment_method',
        'bank_account',
        'proof_of_payment',
        'notes',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if purchase is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed' && $this->confirmed_at !== null;
    }

    /**
     * Check if purchase is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if purchase is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get human readable status
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }
}
