@extends('layouts.app')

@section('title', 'Detail Pembelian Token')
@section('meta_description', 'Detail pembelian token')

@section('content')
    <section class="py-10 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Status Badge -->
            <div class="mb-6">
                <div class="inline-block px-4 py-2 rounded-full text-sm font-medium
                    @switch($purchase->status)
                        @case('pending')
                            bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200
                            @break
                        @case('confirmed')
                            bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200
                            @break
                        @case('cancelled')
                            bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200
                            @break
                    @endswitch
                ">
                    {{ $purchase->getStatusLabel() }}
                </div>
            </div>

            <!-- Main Card -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                    Detail Pembelian Token
                </h1>

                <!-- Success Message -->
                @if (session('success'))
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                        <p class="text-green-800 dark:text-green-200 text-sm">
                            {{ session('success') }}
                        </p>
                    </div>
                @endif

                <!-- Purchase Info -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            ID Pembelian
                        </p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                            #{{ $purchase->id }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Jumlah Token
                        </p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $purchase->quantity }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Tanggal Pesan
                        </p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $purchase->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <p class="text-xs text-blue-600 dark:text-blue-300 uppercase tracking-wide font-medium">
                            Total Harga
                        </p>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400 mt-1">
                            Rp {{ number_format($purchase->total_price, 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                <!-- Bank Account For Payment -->
                @if ($selectedBank && $purchase->status === 'pending')
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Transfer ke Rekening Berikut
                        </h2>

                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Nama Bank</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $selectedBank['bank_name'] }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Nomor Rekening</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-lg font-mono font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $selectedBank['account_number'] }}
                                    </p>
                                    <button
                                        type="button"
                                        onclick="copyToClipboard('{{ $selectedBank['account_number'] }}')"
                                        class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium"
                                    >
                                        Salin
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Atas Nama</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $selectedBank['account_holder'] }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-800 dark:text-blue-300 mb-4">
                                <strong>Perhatian:</strong> Transfer tepat sesuai nominal Rp {{ number_format($purchase->total_price, 0, ',', '.') }} untuk memudahkan verifikasi.
                            </p>
                            <a
                                href="https://wa.me/{{ str_replace(['+', '-', ' '], '', $whatsappNumber) }}?text=Saya%20ingin%20konfirmasi%20pembayaran%20token%20dengan%20ID%20%23{{ $purchase->id }}%20sebesar%20Rp%20{{ number_format($purchase->total_price, 0, ',', '.') }}"
                                target="_blank"
                                class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition"
                            >
                                Hubungi via WhatsApp
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Upload Proof Section -->
                @if ($purchase->status === 'pending')
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Upload Bukti Pembayaran
                        </h2>

                        @if ($errors->has('proof'))
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                                <p class="text-red-800 dark:text-red-200 text-sm">
                                    {{ $errors->first('proof') }}
                                </p>
                            </div>
                        @endif

                        @if ($purchase->proof_of_payment)
                            <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-800 dark:text-green-200 mb-3">
                                    ✓ Bukti pembayaran sudah diupload
                                </p>
                                <a
                                    href="{{ asset('storage/' . $purchase->proof_of_payment) }}"
                                    target="_blank"
                                    class="inline-block text-green-600 dark:text-green-400 hover:underline text-sm font-medium"
                                >
                                    Lihat Bukti Pembayaran
                                </a>
                            </div>
                        @else
                            <form action="{{ route('tokens.upload-proof', $purchase) }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 text-center"
                                    onclick="document.getElementById('proof-input').click()"
                                    style="cursor: pointer;"
                                >
                                    <input
                                        type="file"
                                        id="proof-input"
                                        name="proof_of_payment"
                                        accept="image/*"
                                        class="hidden"
                                        required
                                    >

                                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>

                                    <p class="text-gray-900 dark:text-gray-100 font-medium">
                                        Klik untuk memilih gambar
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                        atau drag dan drop file gambar di sini
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                        Format: JPEG, PNG, GIF (Maks 5MB)
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <button
                                        type="submit"
                                        id="upload-btn"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled
                                    >
                                        Upload Bukti Pembayaran
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                @if ($purchase->proof_of_payment || $purchase->status !== 'pending')
                    <!-- Status Info -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Informasi
                        </h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                            @if ($purchase->status === 'pending' && $purchase->proof_of_payment)
                                Bukti pembayaran Anda sudah diterima. Admin akan mengkonfirmasi dalam beberapa jam.
                            @elseif ($purchase->status === 'confirmed')
                                ✓ Pembayaran Anda telah dikonfirmasi. Token sudah ditambahkan ke akun Anda.
                            @elseif ($purchase->status === 'cancelled')
                                Pembelian token ini telah dibatalkan.
                            @endif
                        </p>
                    </div>
                @endif

                <!-- Back Button -->
                <div class="mt-6 flex gap-3">
                    <a
                        href="{{ route('tokens.history') }}"
                        class="flex-1 text-center bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-100 font-medium py-3 px-4 rounded-lg transition"
                    >
                        Lihat Riwayat
                    </a>
                    <a
                        href="{{ route('tokens.purchase') }}"
                        class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition"
                    >
                        Beli Token Lagi
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Nomor rekening disalin ke clipboard');
            });
        }

        const proofInput = document.getElementById('proof-input');
        const uploadBtn = document.getElementById('upload-btn');

        if (proofInput && uploadBtn) {
            proofInput.addEventListener('change', function() {
                uploadBtn.disabled = !this.files.length;
            });
        }
    </script>
@endsection
