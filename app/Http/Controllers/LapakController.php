<?php

namespace App\Http\Controllers;

use App\Models\LapakProfile;
use App\Http\Controllers\Controller;

class LapakController extends Controller
{
    public function show(LapakProfile $lapak)
    {
        if (! $lapak->is_active) {
            abort(404);
        }
        $hasReported = false;

        if (auth()->check()) {
            $hasReported = $lapak->reports()
                ->where('user_id', auth()->id())
                ->exists();
        }

        $lapak->load([
            'products' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('pushed_at', 'desc');
            },
            'products.images',
            'products.category',
        ]);

        return view('lapak.show', [
            'lapak' => $lapak,
            'hasReported' => $hasReported,
            'meta' => [
                'title' => $lapak->name . ' | Lapak Cimanglid',
                'description' => 'Lapak ' . $lapak->name . ' di marketplace warga Cimanglid. Lihat produk & hubungi penjual langsung.',
                'keywords' => 'lapak cimanglid, ' . $lapak->name . ', jual beli warga',
                'image' => $lapak->profile_image,
            ],
        ]);
    }
}
