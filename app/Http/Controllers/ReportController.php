<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\LapakProfile;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reportable_type' => 'required|in:product,lapak',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'reporter_name' => 'nullable|string|max:100',
            'reporter_email' => 'nullable|email|max:100',
        ]);

        $model = match ($request->reportable_type) {
            'product' => \App\Models\Product::class,
            'lapak'   => \App\Models\LapakProfile::class,
        };

        $reportable = $model::findOrFail($request->reportable_id);

        if ($reportable->reports()
            ->where('user_id', auth()->id())
            ->exists()
        ) {
            return back()
                ->withErrors(['duplicate' => 'Anda sudah pernah melaporkan ini.'], 'report')
                ->withInput();
        }

        $reportable->reports()->create([
            'user_id' => auth()->id(),
            'reporter_name' => $request->reporter_name,
            'reporter_email' => $request->reporter_email,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Terimakasih. Laporan berhasil dikirim. Tim kami akan meninjaunya.');
    }
}
