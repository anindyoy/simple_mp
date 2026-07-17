<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function getDisplayNameAttribute(): ?string
    {
        return $this->payload['displayName'] ?? null;
    }
}
