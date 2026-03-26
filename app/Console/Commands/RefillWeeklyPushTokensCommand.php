<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

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

        return self::SUCCESS;
    }

    private function minimumWeeklyTokens(): int
    {
        return max(0, (int) config('app.weekly_minimum_push_tokens', 3));
    }
}
