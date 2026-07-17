<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefillWeeklyPushTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:refill-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set token user ke nilai minimum mingguan setiap hari Jumat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minimumWeeklyTokens = $this->minimumWeeklyTokens();

        $affectedUsers = User::query()
            ->where('is_admin', false)
            ->where('push_tokens', '<', $minimumWeeklyTokens)
            ->update([
                'push_tokens' => $minimumWeeklyTokens,
            ]);

        $this->info("Refill token mingguan selesai (minimum: {$minimumWeeklyTokens}). User diperbarui: {$affectedUsers}.");
        Log::info('tokens:refill-weekly selesai', ['minimum' => $minimumWeeklyTokens, 'affected_users' => $affectedUsers]);

        return self::SUCCESS;
    }

    private function minimumWeeklyTokens(): int
    {
        return Setting::getIntValue('weekly_minimum_push_tokens', 3);
    }
}
