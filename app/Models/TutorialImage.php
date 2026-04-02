<?php

namespace App\Models;

use App\Models\TutorialPage;
use Illuminate\Database\Eloquent\Model;

class TutorialImage extends Model
{
    protected $guarded = [];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return asset($this->image);
    }


    public function tutorialPage()
    {
        return $this->belongsTo(TutorialPage::class);
    }
}
