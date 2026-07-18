<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

class WhatsappAgentSeeder extends Seeder
{
    public function run(): void
    {
        if (!config('app.hp_admin')) {
            $this->command->warn('Nomor HP admin belum diatur di .env, lewati seeding WhatsappAgent.');
            return;
        }

        WhatsappAgent::query()->updateOrCreate(
            ['phone' => config('app.hp_admin')],
            [
                'active' => true,
                'name' => 'WhatsApp Admin',
                'gender' => null,
                'text' => 'Halo, saya ingin bertanya tentang ' . config('app.name') . '.',
                'image' => null,
            ]
        );
    }
}
