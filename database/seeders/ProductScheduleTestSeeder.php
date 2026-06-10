<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Database\Seeder;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Seeder untuk menguji fitur antrian tampil produk di homepage.
 *
 * Membuat lapak baru dengan 6 produk yang dibuat berurutan per jam,
 * sehingga produk awal sudah tampil sementara yang baru masih antri.
 *
 * Jalankan:
 *   php artisan db:seed --class=ProductScheduleTestSeeder
 *
 * Mode (ubah $mode di bawah):
 *   'mixed'      → sebagian produk langsung tampil, sebagian antri (default)
 *   'all-queued' → semua produk baru dibuat, semua masih antri
 */
class ProductScheduleTestSeeder extends Seeder
{
    // Ubah ke 'all-queued' jika ingin semua produk awalnya belum tampil
    private string $mode = 'mixed';

    public function run(): void
    {
        $delayHours = Setting::getIntValue('product_schedule_delay_hours', 4);

        $category = Category::inRandomOrder()->first();

        if (! $category) {
            $this->command->error('Tidak ada kategori. Jalankan DatabaseSeeder terlebih dahulu.');
            return;
        }

        // Buat user + lapak khusus agar output terfokus
        $suffix = rand(100, 999);

        $user = User::factory()->create([
            'name'        => "Test Antrian {$suffix}",
            'email'       => "test-antrian-{$suffix}@lapak.test",
            'push_tokens' => 0,
        ]);

        $lapak = LapakProfile::factory()->create([
            'user_id' => $user->id,
            'name'    => "Lapak Test Antrian {$suffix}",
        ]);

        // mixed      → P1 dan P2 langsung eligible, P3–P6 masih antri
        // all-queued → semua produk baru dibuat (menit terakhir), P1 tampil, P2–P6 antri
        if ($this->mode === 'all-queued') {
            $productDefs = [];
            for ($i = 0; $i < 6; $i++) {
                $createdAt = now()->subMinutes(6 - $i);
                $productDefs[] = [
                    'no'        => $i + 1,
                    'createdAt' => $createdAt,
                    'pushedAt'  => $createdAt->copy()->subHours(2),
                ];
            }
        } else {
            // P1 dibuat 5 jam lalu, setiap produk berikutnya berjarak 1 menit
            $productDefs = [];
            for ($i = 0; $i < 6; $i++) {
                $createdAt = now()->subHours(5)->addMinutes($i);
                $productDefs[] = [
                    'no'        => $i + 1,
                    'createdAt' => $createdAt,
                    'pushedAt'  => $createdAt->copy()->subHours(2),
                ];
            }
        }

        $created = [];

        Product::withoutEvents(function () use ($productDefs, $lapak, $category, &$created) {
            foreach ($productDefs as $def) {
                $product = Product::create([
                    'title'            => "Produk Antrian #{$def['no']}",
                    'slug'             => 'produk-antrian-' . $def['no'] . '-' . rand(1000, 9999),
                    'description'      => "Produk ke-{$def['no']} dari lapak test antrian. Digunakan untuk menguji fitur antrian tampil homepage.",
                    'price'            => rand(10_000, 500_000),
                    'lapak_id'         => $lapak->id,
                    'category_id'      => $category->id,
                    'is_active'        => true,
                    'can_be_delivered' => false,
                    'created_at'       => $def['createdAt'],
                    'pushed_at'        => $def['pushedAt'],
                ]);

                $created[] = $product;
            }
        });

        ProductScheduleService::rebuild($lapak->id);
        ResponseCache::clear();

        // ─── Output jadwal ───────────────────────────────────────────────
        $schedule = ProductScheduleService::get($lapak->id);
        $now      = now();

        $this->command->newLine();
        $this->command->line('┌─────────────────────────────────────────────────────────────────┐');
        $this->command->line('│  Hasil Jadwal Antrian Produk                                    │');
        $this->command->line('├─────────────────────────────────────────────────────────────────┤');
        $this->command->line("│  Lapak  : {$lapak->name}");
        $this->command->line("│  Delay  : {$delayHours} jam antar produk");
        $this->command->line("│  Waktu  : " . $now->format('d M Y H:i:s'));
        $this->command->line('├─────────────────────────────────────────────────────────────────┤');
        $this->command->newLine();

        foreach ($created as $product) {
            $publishAt = isset($schedule[$product->id])
                ? Carbon::parse($schedule[$product->id])
                : null;

            if (! $publishAt) {
                continue;
            }

            $eligible = $publishAt->lte($now);

            if ($eligible) {
                $statusLabel = '<fg=green;options=bold>✓ TAMPIL SEKARANG</>';
            } else {
                $diff        = $now->diff($publishAt);
                $diffLabel   = $diff->h > 0
                    ? "{$diff->h}j {$diff->i}m lagi"
                    : "{$diff->i}m lagi";
                $statusLabel = "<fg=yellow>⏳ antri — tampil " . $publishAt->format('H:i') . " ({$diffLabel})</>";
            }

            $this->command->line("  <options=bold>{$product->title}</>");
            $this->command->line("    Dibuat  : " . Carbon::parse($product->created_at)->format('d M Y H:i'));
            $this->command->line("    Status  : {$statusLabel}");
            $this->command->newLine();
        }

        $eligibleCount = collect($schedule)
            ->filter(fn($t) => Carbon::parse($t)->lte($now))
            ->count();

        $queuedCount = count($created) - $eligibleCount;

        $this->command->line("  Tampil sekarang : {$eligibleCount} produk");
        $this->command->line("  Dalam antrian   : {$queuedCount} produk");
        $this->command->newLine();
        $this->command->line('  Buka homepage untuk melihat hasilnya.');
        $this->command->line("  Buka /lapak/{$lapak->slug} untuk melihat semua produk (tanpa throttle).");
        $this->command->newLine();
    }
}
