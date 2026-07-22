<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Database\Seeder;

class ProductViewSeeder extends Seeder
{
    protected ?int $count;

    /**
     * @param int|null $count Jumlah ProductView yang digenerate (default 200)
     */
    public function __construct(?int $count = null)
    {
        $this->count = $count;
    }

    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('Tidak ada produk, lewati seeding ProductView.');
            return;
        }

        $total = $this->count ?? rand(100, 200);

        ProductView::factory()
            ->count($total)
            ->make()
            ->each(function (ProductView $view) use ($products) {
                $view->product_id = $products->random()->id;
                $view->save();
            });
    }
}
