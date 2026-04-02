<?php

namespace App\Models;

use App\Models\TutorialPage;
use Illuminate\Database\Eloquent\Model;

class TutorialImage extends Model
{
    protected $guarded = [];

    public function tutorialPage()
    {
        return $this->belongsTo(TutorialPage::class);
    }
}
