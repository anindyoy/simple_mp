<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\LapakProfile;
use App\Http\Controllers\Controller;

class LapakController extends Controller
{
    public function show(LapakProfile $lapak)
    {
        if (! $lapak->is_active) {
            abort(404);
        }

        $externalLinks = collect($lapak->external_links ?? [])
            ->filter(function ($item) {
                return is_array($item)
                    && filled($item['label'] ?? null)
                    && filled($item['link'] ?? null)
                    && filter_var($item['link'], FILTER_VALIDATE_URL);
            })
            ->map(function (array $item): array {
                return [
                    'label' => trim($item['label']),
                    'link' => $item['link'],
                ];
            })
            ->values()
            ->all();

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
            'products.category',
        ]);

        $region = Setting::getValue('site_region', 'Cimanglid');

        return view('lapak.show', [
            'lapak' => $lapak,
            'externalLinks' => $externalLinks,
            'hasReported' => $hasReported,
            'meta' => [
                'title' => $lapak->name . ' | Lapak ' . $region,
                'description' => 'Lapak ' . $lapak->name . ' di marketplace warga ' . $region . '. Lihat produk & hubungi penjual langsung.',
                'keywords' => 'lapak ' . strtolower($region) . ', ' . $lapak->name . ', jual beli warga',
                'image' => $lapak->profile_image,
            ],
        ]);
    }
}
