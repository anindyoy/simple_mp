<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;

class UpdateCatalogSeeder extends Seeder
{
    /**
     * Jumlah total iterasi create/update yang dijalankan, didistribusikan
     * merata ke tiap hari dalam range tanggal (lihat $fromDate).
     */
    public int $totalRuns = 3;

    /**
     * Tanggal awal range pembuatan data. Default null = hari ini saja.
     * Jika diisi (mis. 3 hari sebelum hari ini), seeder membuat data
     * untuk tiap hari dari tanggal ini sampai hari ini.
     */
    public Carbon|string|null $fromDate = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lapaks      = LapakProfile::all();
        $categories  = Category::all();
        $products    = Product::all();

        $changed          = false;
        $createdLapakCount = 0;
        $createdCount     = 0;
        $updatedCount     = 0;

        $days        = $this->resolveDays();
        $runsPerDay  = $this->distributeRuns($this->totalRuns, count($days));
        $totalIterations = array_sum($runsPerDay);
        $iteration   = 0;

        foreach ($days as $dayIndex => $day) {
            for ($j = 0; $j < $runsPerDay[$dayIndex]; $j++) {
                $iteration++;
                $isLast     = $iteration === $totalIterations;
                $mustUpdate = $isLast && $updatedCount === 0 && $products->isNotEmpty();

                // Kondisi buat lapak baru
                if (! $mustUpdate && random_int(1, 3) === 1) {
                    // Cek apakah pada tanggal ini sudah ada lapak yang baru dibuat
                    $newLapakOnDay = LapakProfile::query()
                        ->whereDate('created_at', $day)
                        ->exists();

                    if (! $newLapakOnDay) {
                        // Belum ada lapak baru pada tanggal ini — skip, lanjut ke kode update/create product
                        $lapak = $this->createRandomLapak($day);
                        $lapaks->push($lapak);
                        $createdLapakCount++;
                        $changed = true;
                        continue;
                    }
                }

                $action = ($mustUpdate || ($products->isNotEmpty() && random_int(0, 1) === 1))
                    ? 'update'
                    : 'create';

                if ($action === 'create') {
                    $product = $this->createRandomProduct($lapaks, $categories, $day);
                    if ($product) {
                        $products->push($product);
                        $createdCount++;
                        $changed = true;
                    }
                    continue;
                }

                $updated = $this->updateRandomProductPushTime($products, $day);
                $updatedCount += $updated ? 1 : 0;
                $changed = $updated || $changed;
            }
        }

        if ($changed) {
            ResponseCache::clear();
            ProductScheduleService::forgetEligible();
        }

        $summary = [
            'created_lapak' => $createdLapakCount,
            'created'       => $createdCount,
            'updated'       => $updatedCount,
            'total_runs'    => $totalIterations,
            'date_from'     => $days[0]->toDateString(),
            'date_to'       => $days[count($days) - 1]->toDateString(),
        ];

        $this->info(sprintf(
            'UpdateCatalogSeeder summary: created_lapak=%d, created=%d, updated=%d, total_runs=%d, date_from=%s, date_to=%s',
            $createdLapakCount,
            $createdCount,
            $updatedCount,
            $totalIterations,
            $summary['date_from'],
            $summary['date_to'],
        ));
        Log::info('UpdateCatalogSeeder selesai', $summary);
    }

    protected function info(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    /**
     * @return array<int, Carbon> Daftar tanggal dari $fromDate sampai hari ini (inklusif).
     */
    private function resolveDays(): array
    {
        $to   = today();
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->startOfDay() : $to->copy();

        if ($from->greaterThan($to)) {
            $from = $to->copy();
        }

        $days = [];
        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $days[] = $cursor->copy();
        }

        return $days;
    }

    /**
     * Bagi $totalRuns merata ke $dayCount hari; sisa pembagian dibagikan
     * secara acak ke sebagian hari agar totalnya tetap sama dengan $totalRuns.
     *
     * @return array<int, int>
     */
    private function distributeRuns(int $totalRuns, int $dayCount): array
    {
        if ($dayCount <= 0) {
            return [];
        }

        $base      = intdiv($totalRuns, $dayCount);
        $remainder = $totalRuns % $dayCount;

        $runs = array_fill(0, $dayCount, $base);

        foreach (collect(range(0, $dayCount - 1))->shuffle()->take($remainder) as $index) {
            $runs[$index]++;
        }

        return $runs;
    }

    /**
     * Timestamp acak pada $day. Jika $day adalah hari ini, dibatasi
     * agar tidak melebihi waktu sekarang (hindari timestamp masa depan).
     */
    private function randomTimestampOn(Carbon $day): Carbon
    {
        $maxSeconds = $day->isToday()
            ? now()->diffInSeconds($day->copy()->startOfDay())
            : 86399;

        return $day->copy()->startOfDay()->addSeconds(random_int(0, max($maxSeconds, 0)));
    }

    private function createRandomLapak(Carbon $day): LapakProfile
    {
        $createdAt = $this->randomTimestampOn($day);

        return LapakProfile::factory()->create([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createRandomProduct(Collection $lapaks, Collection $categories, Carbon $day): ?Product
    {
        $lapak    = $lapaks->random();
        $category = $categories->random();

        if (! $lapak || ! $category) {
            return null;
        }

        $title     = $this->makeProductTitle($category->category_name);
        $createdAt = $this->randomTimestampOn($day);

        // withoutEvents agar observer creating tidak overwrite pushed_at
        $product = Product::withoutEvents(
            fn() =>
            Product::factory()->create([
                'title'       => $title,
                'category_id' => $category->id,
                'lapak_id'    => $lapak->id,
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
                'pushed_at'   => $createdAt->copy()->subHours(random_int(1, 5)),
            ])
        );

        ProductScheduleService::rebuild($lapak->id);

        return $product;
    }

    private function updateRandomProductPushTime(Collection $products, Carbon $day): bool
    {
        if ($products->isEmpty()) {
            return false;
        }

        $pushedAt = $this->randomTimestampOn($day)->subHours(random_int(1, 5));

        $products->random()->updateQuietly([
            'pushed_at' => $pushedAt,
        ]);

        return true;
    }

    private function makeProductTitle(string $categoryName): string
    {
        return $categoryName . ' ' . Str::headline(fake()->words(2, true));
    }
}
