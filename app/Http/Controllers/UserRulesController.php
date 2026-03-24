<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Routing\Controller;

class UserRulesController extends Controller
{
    public function index()
    {
        return view('rules.index', [
            'rulesContent' => Setting::getValue('user_rules_content', ''),
            'meta' => [
                'title' => 'Peraturan Pengguna | Jual Beli Cimanglid',
                'description' => 'Halaman peraturan dan ketentuan penggunaan untuk pengguna Jual Beli Cimanglid.',
                'keywords' => 'peraturan pengguna, ketentuan, jual beli cimanglid',
            ],
        ]);
    }
}
