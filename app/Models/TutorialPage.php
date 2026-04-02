<?php

namespace App\Models;

use App\Models\TutorialImage;
use Illuminate\Database\Eloquent\Model;

class TutorialPage extends Model
{
protected $guarded = [];

    public function images()
    {
        return $this->hasMany(TutorialImage::class)->orderBy('sort_order');
    }
}
