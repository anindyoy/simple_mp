<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

class WhatsappAgentSeeder extends Seeder
{
    public function run(): void
    {
        WhatsappAgent::query()->updateOrCreate(
            ['phone' => config('app.hp_admin')],
            [
                'active' => true,
                'name' => 'WhatsApp Admin',
                'text' => 'Halo, saya ingin bertanya tentang ' . config('app.name') . '.',
                'image' => null,
            ]
        );
    }
}