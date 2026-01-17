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

        return view('lapak.show', [
            'lapak' => $lapak,
            'meta' => [
                'title' => $lapak->nama_lapak . ' | Lapak Cimanglid',
                'description' => 'Lapak ' . $lapak->nama_lapak . ' di marketplace warga Cimanglid. Lihat produk & hubungi penjual langsung.',
                'keywords' => 'lapak cimanglid, ' . $lapak->nama_lapak . ', jual beli warga',
                'image' => $lapak->foto_profil,
            ],
        ]);
    }
}
