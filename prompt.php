Lengkapi info meta di setiap halaman

<?
app.blade:
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jual Beli Cimanglid')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 dark:bg-gray-900">

    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50">
        <div class="flex flex-wrap justify-between items-center container mx-auto">
            <a href="/" class="flex items-center">
                <span class="self-center text-xl font-bold whitespace-nowrap dark:text-white text-blue-600 tracking-tight">
                    Jual Beli <span class="text-orange-500">Cimanglid</span>
                </span>
            </a>
            <div class="flex items-center lg:order-2">
                <a href="#" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 lg:px-5 lg:py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Pasang Iklan</a>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="p-4 bg-white md:p-8 lg:p-10 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="mx-auto max-w-screen-xl text-center">
            <span class="flex justify-center items-center text-xl font-bold text-gray-900 dark:text-white uppercase tracking-widest">
                JUAL BELI CIMANGLID
            </span>
            <p class="my-6 text-gray-500 dark:text-gray-400 text-sm italic">Warga Bantu Warga - Transaksi via WhatsApp & Telegram.</p>
            <span class="text-xs text-gray-400 sm:text-center">© 2026 Jual Beli Cimanglid.</span>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</body>
</html>

<?php

namespace App\Http\Controllers;

use App\Models\LapakProfile;
use App\Http\Controllers\Controller;

class LapakController extends Controller
{
    public function show(LapakProfile $lapak)
    {
        $lapak->load([
            'products' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('pushed_at', 'desc');
            },
            'products.images',
            'products.category',
        ]);

        return view('lapak.show', compact('lapak'));
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category');

        $products = Product::with([
                'lapak',
                'images' => function ($query) {
                    $query->where('is_primary', true);
                }
            ])
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('pushed_at', 'desc')
            ->paginate(16)
            ->withQueryString(); // penting agar filter tetap saat pagination

        $categories = Category::orderBy('category_name')->get();

        return view('main', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $categoryId,
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['lapak', 'category', 'images']);
        return view('product-detail', compact('product'));
    }
}
