<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Routing\Controller;

class UserRulesController extends Controller
{
    public function index()
    {
        $siteTitle = Setting::getValue('site_title', 'Lapak Online Warga');
        $region = Setting::getValue('site_region', 'Cimanglid');

        return view('rules.index', [
            'rulesContent' => Setting::getValue('user_rules_content', ''),
            'meta' => [
                'title' => 'Peraturan Pengguna | ' . $siteTitle . ' ' . $region,
                'description' => 'Halaman peraturan dan ketentuan penggunaan untuk pengguna ' . $siteTitle . ' ' . $region . '.',
                'keywords' => 'peraturan pengguna, ketentuan, jual beli ' . strtolower($region),
            ],
        ]);
    }
}
