<?php

namespace App\Http\CacheProfiles;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

class SkipWhenFlashData extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        if (! parent::shouldCacheRequest($request)) {
            return false;
        }

        $flash = $request->session()->get('_flash', ['new' => [], 'old' => []]);

        return empty($flash['new']) && empty($flash['old']);
    }
}
