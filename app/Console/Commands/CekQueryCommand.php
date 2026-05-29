<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
        $this->info('Running product data checks...');

        $withCondition = DB::table('products')->whereNotNull('condition')->count();
        $canBeDelivered = DB::table('products')->where('can_be_delivered', 1)->count();
        $condInSupported = DB::table('products')->whereIn('category_id', [2,3,4])->whereNotNull('condition')->count();

        $this->line('with_condition: ' . $withCondition);
        $this->line('can_be_delivered: ' . $canBeDelivered);
        $this->line('cond_in_supported_categories: ' . $condInSupported);

        $this->line('\nSample rows (id, title, condition, can_be_delivered, category_id):');
        $samples = DB::table('products')->select('id','title','condition','can_be_delivered','category_id')->limit(8)->get();

        foreach ($samples as $s) {
            $this->line(json_encode($s));
            logger()->debug('product-card-debug-cli', (array) $s);
        }

        return 0;
    }
}
