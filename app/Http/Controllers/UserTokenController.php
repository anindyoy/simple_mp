<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TokenPurchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UserTokenController extends Controller
{
    /**
     * Show token purchase page
     */
    public function showPurchase(): View
    {
        $user = Auth::user();
        $tokenPrice = Setting::getIntValue('token_price', 2000);
        $minTokens = Setting::getIntValue('min_tokens_for_normal_price', 5);
        $bankAccounts = $this->getBankAccounts();
        $whatsappNumber = Setting::getValue('token_purchase_whatsapp', '');

        return view('tokens.purchase', compact(
            'user',
            'tokenPrice',
            'minTokens',
            'bankAccounts',
            'whatsappNumber'
        ));
    }

    /**
     * Store token purchase request
     */
    public function storePurchase(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'bank_account' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tokenPrice = Setting::getIntValue('token_price', 2000);
        $totalPrice = $validated['quantity'] * $tokenPrice;

        // Verify bank account is valid
        $bankAccounts = $this->getBankAccounts();
        $selectedBank = collect($bankAccounts)
            ->first(fn($account) => $account['account_number'] === $validated['bank_account']);

        if (!$selectedBank) {
            return redirect()
                ->back()
                ->withErrors(['bank_account' => 'Rekening bank tidak valid']);
        }

        // Create token purchase record
        $purchase = $user->tokenPurchases()->create([
            'quantity' => $validated['quantity'],
            'total_price' => $totalPrice,
            'status' => 'pending',
            'bank_account' => $validated['bank_account'],
            'notes' => $validated['notes'] ?? null,
            'payment_method' => 'bank_transfer',
        ]);

        return redirect()
            ->route('tokens.show-purchase', $purchase)
            ->with('success', 'Permintaan pembelian token berhasil dibuat. Silakan transfer sesuai nominal ke rekening yang tertera.');
    }

    /**
     * Show single purchase details for payment confirmation
     */
    public function showPurchaseDetails(TokenPurchase $purchase): View
    {
        // Authorize user
        abort_if($purchase->user_id !== Auth::id(), 403);

        $bankAccounts = $this->getBankAccounts();
        $selectedBank = collect($bankAccounts)
            ->first(fn($account) => $account['account_number'] === $purchase->bank_account);

        $whatsappNumber = Setting::getValue('token_purchase_whatsapp', '');

        return view('tokens.purchase-details', compact(
            'purchase',
            'selectedBank',
            'whatsappNumber'
        ));
    }

    /**
     * Upload proof of payment
     */
    public function uploadProof(Request $request, TokenPurchase $purchase): RedirectResponse
    {
        // Authorize user
        abort_if($purchase->user_id !== Auth::id(), 403);

        // Only allow upload for pending purchases
        if ($purchase->status !== 'pending') {
            return redirect()
                ->back()
                ->withErrors(['proof' => 'Hanya pembelian dengan status pending yang bisa upload bukti pembayaran.']);
        }

        $validated = $request->validate([
            'proof_of_payment' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'], // 5MB
        ]);

        // Store file
        $path = $request->file('proof_of_payment')
            ->store('token-purchases/' . $purchase->id, 'public');

        $purchase->update([
            'proof_of_payment' => $path,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Bukti pembayaran berhasil diupload. Menunggu konfirmasi admin.');
    }

    /**
     * Show token purchase history
     */
    public function history(): View
    {
        $user = Auth::user();
        $purchases = $user->tokenPurchases()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $totalTopUp = $user->tokenPurchases()
            ->where('status', 'confirmed')
            ->sum('quantity');
        $totalSpent = $user->tokenPurchases()
            ->where('status', 'confirmed')
            ->sum('total_price');

        return view('tokens.history', compact(
            'user',
            'purchases',
            'totalTopUp',
            'totalSpent'
        ));
    }

    /**
     * Get bank accounts from settings
     */
    protected function getBankAccounts(): array
    {
        $stored = Setting::getValue('token_bank_accounts');
        if (blank($stored)) {
            return [];
        }

        $decoded = json_decode($stored, true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn($account) => is_array($account) && isset($account['bank_name'], $account['account_number'], $account['account_holder']))
            ->values()
            ->all();
    }
}
