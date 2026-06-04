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
        dd($this->getUserNameAndProductIds());
    }

    private function getUserCanDeliveredInLapak()
    {
        return User::whereHas('lapak', fn($query) => $query->where('can_be_delivered', 1))->first();
    }

    private function getUserWithProductHavingMediaMoreThanOne()
    {
        return User::whereHas('lapak.products', fn($q) => $q->has('media', '>', 1))->first();
    }

    private function getUserNameAndProductIds()
    {
        $user = $this->getUserWithProductHavingMediaMoreThanOne();
        $userName = $user->name ?? 'Tidak ditemukan';

        if (! $user) {
            return [$userName, collect()];
        }

        $productIds = Product::has('media', '>', 1)
            ->whereHas('lapak', fn($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        return [$userName, $productIds];
    }
}
