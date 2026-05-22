<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

class WhatsappAgentSeeder extends Seeder
{
    public function run(): void
    {
        WhatsappAgent::query()->updateOrCreate(
            ['phone' => '628567851359'],
            [
                'active' => true,
                'name' => 'WhatsApp Admin',
                'text' => 'Halo, saya ingin bertanya tentang Simple MP.',
                'image' => null,
            ]
        );
    }
}