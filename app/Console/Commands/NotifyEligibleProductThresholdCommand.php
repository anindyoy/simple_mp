<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\ProductScheduleService;

class NotifyEligibleProductThresholdCommand extends Command
{
    protected $signature = 'products:notify-eligible-threshold';

    protected $description = 'Kirim peringatan Telegram saat jumlah produk eligible melewati ambang batas.';

    public function handle(): int
    {
        $count = ProductScheduleService::getEligibleProductIds()->count();

        if ($count <= 900) {
            $this->info("Jumlah eligible produk aman: {$count}.");

            return 0;
        }

        if (! Cache::add('products.eligible_threshold_notified', true, now()->addDay())) {
            $this->info('Notifikasi ambang batas sudah dikirim dalam 24 jam terakhir.');

            return 0;
        }

        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (blank($token) || blank($chatId)) {
            $this->warn('Konfigurasi Telegram belum lengkap, notifikasi dilewati.');

            return 0;
        }

        Http::connectTimeout(5)
            ->timeout(10)
            ->retry(2, 500)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'parse_mode' => 'HTML',
                'text' => implode("\n", [
                    '⚠️ <b>Peringatan Kapasitas eligible_ids</b>',
                    '',
                    "<code>eligibleProductIds</code> telah mencapai <b>{$count}</b> produk (batas rekomendasi: 900).",
                    'Pertimbangkan refactor <code>resolveEligibleProductIds()</code> dan <code>whereIn</code> di ProductController.',
                    '',
                    'Domain: ' . config('app.url'),
                ]),
            ])
            ->throw();

        $this->info("Notifikasi ambang batas berhasil dikirim (jumlah: {$count}).");

        return 0;
    }
}
