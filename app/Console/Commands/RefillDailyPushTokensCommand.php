<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefillDailyPushTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:refill-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set token angkat produk user ke nilai minimum harian setiap hari';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minimumDailyTokens = $this->minimumDailyTokens();

        $affectedUsers = User::query()
            ->where('is_admin', false)
            ->where('push_tokens', '<', $minimumDailyTokens)
            ->update([
                'push_tokens' => $minimumDailyTokens,
            ]);

        $this->info("Refill token angkat produk harian selesai (minimum: {$minimumDailyTokens}). User diperbarui: {$affectedUsers}.");
        Log::info('tokens:refill-daily selesai', ['minimum' => $minimumDailyTokens, 'affected_users' => $affectedUsers]);

        return self::SUCCESS;
    }

    private function minimumDailyTokens(): int
    {
        return Setting::getIntValue('daily_minimum_push_tokens', 2);
    }
}
