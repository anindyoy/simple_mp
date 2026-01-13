<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushLog extends Model
{
    // Kita matikan updated_at karena hanya butuh data record saat push terjadi
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'pushed_at' => 'datetime',
    ];
}
