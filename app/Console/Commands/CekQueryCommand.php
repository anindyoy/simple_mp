<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Storage;

class CekQueryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cek:query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Report::factory()->count(20)->create();
        // $this->call(new ProductSeeder(6));
        // $this->call(new ProductSeeder(3, 'updatePushed'));
        User::factory(10)->create();
    }
}
