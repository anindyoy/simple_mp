<?php

namespace App\Console\Commands;

use App\Services\VisitorStatsService;
use Illuminate\Console\Command;

class CalculateVisitorStatsCommand extends Command
{
    protected $signature = 'visitors:calculate-24h';

    protected $description = 'Hitung jumlah pengunjung unik (berdasarkan IP di ProductView) dalam 24 jam terakhir.';

    public function handle(): int
    {
        $count = VisitorStatsService::calculate();

        $this->info("Pengunjung unik 24 jam terakhir: {$count}.");

        return self::SUCCESS;
    }
}
