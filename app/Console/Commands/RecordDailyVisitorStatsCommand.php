<?php

namespace App\Console\Commands;

use App\Services\VisitorStatsService;
use Illuminate\Console\Command;

class RecordDailyVisitorStatsCommand extends Command
{
    protected $signature = 'visitors:record-daily';

    protected $description = 'Catat jumlah pengunjung unik hari ini ke tabel visitor_stats.';

    public function handle(): int
    {
        VisitorStatsService::recordDaily();

        $this->info('Data pengunjung harian berhasil dicatat.');

        return self::SUCCESS;
    }
}