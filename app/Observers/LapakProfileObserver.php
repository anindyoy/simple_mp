<?php

namespace App\Observers;

use App\Models\LapakProfile;
use App\Services\ProductScheduleService;

class LapakProfileObserver
{
    public function saved(LapakProfile $lapak): void
    {
        ProductScheduleService::forget($lapak->id);
    }
}
