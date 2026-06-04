<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Report;
use App\Models\Product;
use App\Models\Category;
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

    /**s
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
        // $userName = User::whereHas('lapak', fn($query) => $query->where('can_be_delivered', 1))->first()->name;

        // Get nama user yang memiliki lapak dengan produk yang memiliki media lebih dari 1
        // $user = User::whereHas('lapak.products', fn($q) => $q->has('media', '>', 1))->first();
        // $userName = $user->name ?? 'Tidak ditemukan';

        // // Tampilkan list produk ids yang memiliki media lebih dari 1 milik user di atas
        // if (! $user) {
        //     dd($userName, collect());
        // }

        // $productIds = Product::has('media', '>', 1)
        //     ->whereHas('lapak', fn($q) => $q->where('user_id', $user->id))
        //     ->pluck('id');

        // dd($userName, $productIds);

        Category::whereIn('id', [3, 4, 5, 7, 10])->update(['supports_condition' => true]);
    }
}
