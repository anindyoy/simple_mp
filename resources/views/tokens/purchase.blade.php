@extends('layouts.app')

@section('title', 'Beli Token')
@section('meta_description', 'Beli token untuk mendapatkan lebih banyak fitur')
@section('meta_keywords', 'token, beli token')

@section('content')
    <section class="py-10 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Check if user is authenticated -->
            @guest
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8 text-center">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Silakan Login Terlebih Dahulu
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Anda harus login untuk membeli token.
                    </p>
                    <button onclick="document.getElementById('auth-login-modal').showModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg">
                        Login
                    </button>
                </div>
            @else
                <!-- Token Balance Card -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-lg p-6 md:p-8 mb-6 text-white">
                    <p class="text-blue-100 mb-2">Saldo Token Anda</p>
                    <h2 class="text-4xl md:text-5xl font-bold mb-4">
                        {{ $user->push_tokens }}
                    </h2>
                    <div class="flex gap-4 flex-wrap">
                        <a href="{{ route('tokens.history') }}" class="bg-white/20 hover:bg-white/30 text-white font-medium py-2 px-4 rounded-lg transition">
                            Lihat Riwayat
                        </a>
                    </div>
                </div>

                <!-- Token Purchase Form -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        Beli Token
                    </h1>

                    @if ($errors->any())
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-red-800 dark:text-red-200 mb-2">
                                Terjadi Error
                            </h3>
                            <ul class="list-disc list-inside text-red-700 dark:text-red-300 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('tokens.store-purchase') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Token Quantity Input -->
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Jumlah Token <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    min="1"
                                    max="10000"
                                    value="{{ old('quantity', 5) }}"
                                    oninput="updatePriceEstimate()"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required
                                >
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                    token
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Harga: Rp {{ number_format($tokenPrice, 0, ',', '.') }} per token
                            </p>
                        </div>

                        <!-- Price Estimate -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700 dark:text-gray-300">Harga Satuan:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    Rp <span id="unit-price">{{ number_format($tokenPrice, 0, ',', '.') }}</span>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">Total Harga:</span>
                                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    Rp <span id="total-price">{{ number_format($tokenPrice * 5, 0, ',', '.') }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Bank Account Selection -->
                        <div>
                            <label for="bank_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Transfer ke Rekening <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-3">
                                @forelse ($bankAccounts as $bank)
                                    <label class="flex items-start p-4 border border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <input
                                            type="radio"
                                            name="bank_account"
                                            value="{{ $bank['account_number'] }}"
                                            {{ old('bank_account', $bankAccounts[0]['account_number'] ?? '') === $bank['account_number'] ? 'checked' : '' }}
                                            class="mt-1 w-4 h-4 text-blue-600 focus:ring-blue-500"
                                            required
                                        >
                                        <div class="ml-3 flex-1">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $bank['bank_name'] }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $bank['account_number'] }} a.n. {{ $bank['account_holder'] }}
                                            </p>
                                        </div>
                                    </label>
                                @empty
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-yellow-800 dark:text-yellow-200">
                                        <p class="text-sm">Belum ada rekening bank yang tersedia. Hubungi admin.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Catatan (Opsional)
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                maxlength="500"
                                placeholder="Catatan tambahan jika ada..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Maks 500 karakter
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                            {{ count($bankAccounts) > 0 ? '' : 'disabled' }}
                        >
                            @if (count($bankAccounts) > 0)
                                Lanjut ke Pembayaran
                            @else
                                Tidak Ada Rekening Bank
                            @endif
                        </button>
                    </form>

                    <!-- Info Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Cara Pembelian Token
                        </h3>
                        <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold">
                                    1
                                </span>
                                <span>Pilih jumlah token yang ingin dibeli</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold">
                                    2
                                </span>
                                <span>Pilih rekening bank tujuan transfer</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold">
                                    3
                                </span>
                                <span>Klik "Lanjut ke Pembayaran"</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold">
                                    4
                                </span>
                                <span>Transfer sesuai nominal ke rekening yang tertera</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold">
                                    5
                                </span>
                                <span>Upload bukti pembayaran</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold">
                                    6
                                </span>
                                <span>Tunggu konfirmasi admin (biasanya dalam beberapa jam)</span>
                            </li>
                        </ol>
                    </div>
                </div>
            @endguest
        </div>
    </section>

    <script>
        const tokenPrice = {{ $tokenPrice }};

        function updatePriceEstimate() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const totalPrice = quantity * tokenPrice;

            document.getElementById('unit-price').textContent =
                new Intl.NumberFormat('id-ID').format(tokenPrice);
            document.getElementById('total-price').textContent =
                new Intl.NumberFormat('id-ID').format(totalPrice);
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', updatePriceEstimate);
    </script>
@endsection
