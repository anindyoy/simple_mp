<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        // Get nama user yang memiliki lapak dengan can_be_delivered = 1
        $data = User::whereHas('lapak', fn($query) => $query->where('can_be_delivered', 1))->first()->name;
        dd($data);
    }
}
