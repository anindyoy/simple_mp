<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

class WhatsappAgentObserver
{
    public function created(WhatsappAgent $whatsappAgent): void
    {
        Cache::forget('whatsapp_active_agents');
    }

    public function updated(WhatsappAgent $whatsappAgent): void
    {
        Cache::forget('whatsapp_active_agents');
    }

    public function deleted(WhatsappAgent $whatsappAgent): void
    {
        Cache::forget('whatsapp_active_agents');
    }

    public function restored(WhatsappAgent $whatsappAgent): void
    {
        Cache::forget('whatsapp_active_agents');
    }

    public function forceDeleted(WhatsappAgent $whatsappAgent): void
    {
        Cache::forget('whatsapp_active_agents');
    }
}