@extends('layouts.app')

@section('title', 'Riwayat Pembelian Token')
@section('meta_description', 'Riwayat pembelian token Anda')

@section('content')
    <section class="py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header with Balance -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <!-- Balance Card -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl shadow-lg p-6 text-white col-span-1 md:col-span-1">
                    <p class="text-blue-100 text-sm mb-2">Saldo Token Saat Ini</p>
                    <h2 class="text-4xl font-bold">{{ $user->push_tokens }}</h2>
                </div>

                <!-- Total Top Up -->
                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-2xl shadow-lg p-6 text-white col-span-1 md:col-span-1">
                    <p class="text-green-100 text-sm mb-2">Total Token Terbeli</p>
                    <h2 class="text-4xl font-bold">{{ $totalTopUp }}</h2>
                </div>

                <!-- Total Spent -->
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl shadow-lg p-6 text-white col-span-1 md:col-span-1">
                    <p class="text-purple-100 text-sm mb-2">Total Pengeluaran</p>
                    <h2 class="text-3xl font-bold">Rp {{ number_format($totalSpent) }}</h2>
                </div>
            </div>

            <!-- Buy Button -->
            <div class="mb-6">
                <a
                    href="{{ route('tokens.purchase') }}"
                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition"
                >
                    + Beli Token Baru
                </a>
            </div>

            <!-- History Table/Cards -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">
                        Riwayat Pembelian Token
                    </h1>
                </div>

                @if ($purchases->count() > 0)
                    <!-- Desktop View -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        ID Pembelian
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Jumlah Token
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Total Harga
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Status
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Tanggal
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchases as $purchase)
                                    <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            #{{ $purchase->id }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $purchase->quantity }} token
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($purchase->total_price) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
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
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $purchase->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <a
                                                href="{{ route('tokens.show-purchase', $purchase) }}"
                                                class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
                                            >
                                                Lihat
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View -->
                    <div class="md:hidden space-y-4 p-6">
                        @foreach ($purchases as $purchase)
                            <div class="border border-gray-200 dark:border-gray-800 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">ID Pembelian</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">#{{ $purchase->id }}</p>
                                    </div>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
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
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Jumlah Token</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $purchase->quantity }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Total Harga</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($purchase->total_price) }}
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                    {{ $purchase->created_at->format('d/m/Y H:i') }}
                                </p>

                                <a
                                    href="{{ route('tokens.show-purchase', $purchase) }}"
                                    class="inline-block text-blue-600 dark:text-blue-400 hover:underline font-medium text-sm"
                                >
                                    Lihat Detail →
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if ($purchases->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                            {{ $purchases->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                            Belum Ada Riwayat Pembelian
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Mulai beli token sekarang untuk mendapatkan fitur eksklusif.
                        </p>
                        <a
                            href="{{ route('tokens.purchase') }}"
                            class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition"
                        >
                            Beli Token Sekarang
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
