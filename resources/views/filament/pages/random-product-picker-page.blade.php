<x-filament::page>
    <div class="space-y-6">
        <div class="space-y-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Klik tombol di bawah untuk memilih satu produk aktif secara acak dari seluruh lapak yang sedang tampil.
            </p>

            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span>Produk aktif: <span class="font-medium text-gray-700 dark:text-gray-200">{{ number_format($activeProductCount) }}</span></span>
                <span>Lapak aktif: <span class="font-medium text-gray-700 dark:text-gray-200">{{ number_format($activeLapakCount) }}</span></span>
            </div>

            <div class="flex flex-wrap items-start gap-3">
                <div>
                    <label for="maxProductHistory" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Maks. riwayat produk
                    </label>
                    <x-filament::input.wrapper class="w-32">
                        <x-filament::input
                            type="number"
                            id="maxProductHistory"
                            wire:model="maxProductHistory"
                            min="0"
                        />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Rekomendasi: {{ $this->recommendedMaxProductHistory() }}
                    </p>
                </div>

                <div>
                    <label for="maxLapakHistory" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Maks. riwayat toko
                    </label>
                    <x-filament::input.wrapper class="w-32">
                        <x-filament::input
                            type="number"
                            id="maxLapakHistory"
                            wire:model="maxLapakHistory"
                            min="0"
                        />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Rekomendasi: {{ $this->recommendedMaxLapakHistory() }}
                    </p>
                </div>

                <div>
                    <span class="invisible mb-1 block text-xs font-medium">&nbsp;</span>
                    <x-filament::button wire:click="generate" icon="heroicon-o-sparkles">
                        Generate Produk Acak
                    </x-filament::button>
                </div>
            </div>
        </div>

        @if ($hasGenerated && $product)
            <div
                class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                x-data="{
                    copied: false,
                    copyText(text) {
                        const fallbackCopy = () => {
                            const el = document.createElement('textarea');
                            el.value = text;
                            el.setAttribute('readonly', '');
                            el.style.position = 'fixed';
                            el.style.opacity = '0';
                            document.body.appendChild(el);
                            el.select();
                            el.setSelectionRange(0, text.length);
                            document.execCommand('copy');
                            document.body.removeChild(el);
                        };

                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).catch(() => fallbackCopy());
                        } else {
                            fallbackCopy();
                        }

                        this.copied = true;
                        setTimeout(() => this.copied = false, 1500);
                    },
                }"
            >
                <div class="grid gap-4 md:grid-cols-[160px_1fr]">
                    <img
                        src="{{ $product->thumbnail_url }}"
                        alt="{{ $product->title }}"
                        class="h-40 w-40 rounded-lg object-cover"
                    />

                    <div class="space-y-1">
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $product->title }}
                        </div>

                        <div class="grid gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                            <div>Kategori: {{ $product->category?->category_name ?? '-' }}</div>

                            @if ($product->hasCondition())
                                <div>Kondisi: {{ $product->conditionLabel() }}</div>
                            @endif

                            <div>Harga: Rp {{ number_format((float) $product->price, 0, ',', '.') }}</div>
                            <div>Bisa Diantar: {{ $product->can_be_delivered ? 'Ya' : 'Tidak' }}</div>
                            {{-- <div>Dilihat: {{ number_format($product->views_count) }} kali</div> --}}
                            <div>Lapak: {{ $product->lapak?->name ?? '-' }}</div>
                            <div>Dibuat: {{ $product->created_at->translatedFormat('d F Y') }}</div>
                        </div>

                        <div class="pt-1 text-sm">
                            <a
                                href="{{ route('product.show', $product) }}"
                                target="_blank"
                                class="text-primary-600 underline hover:text-primary-500"
                            >
                                Lihat produk di halaman publik
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <x-filament::button
                        type="button"
                        size="sm"
                        color="gray"
                        x-on:click="copyText({{ \Illuminate\Support\Js::from($this->getCopyText()) }})"
                    >
                        <span x-show="!copied">Copy Hasil ke Clipboard</span>
                        <span x-show="copied">Tersalin</span>
                    </x-filament::button>
                </div>
            </div>
        @elseif ($hasGenerated)
            <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                Tidak ada produk aktif yang bisa dipilih saat ini.
            </div>
        @endif

        {{-- History --}}
        @if (count($history) > 0)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        Riwayat Produk Acak ({{ count($history) }} terakhir)
                    </h3>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($history as $entry)
                        @php
                            $p = $entry->product;
                        @endphp
                        <div
                            class="px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            x-data="{
                                copied_{{ $entry->id }}: false,
                                copyText_{{ $entry->id }}(text) {
                                    const fallbackCopy = () => {
                                        const el = document.createElement('textarea');
                                        el.value = text;
                                        el.setAttribute('readonly', '');
                                        el.style.position = 'fixed';
                                        el.style.opacity = '0';
                                        document.body.appendChild(el);
                                        el.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(el);
                                    };
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(text).catch(() => fallbackCopy());
                                    } else {
                                        fallbackCopy();
                                    }
                                    this.copied_{{ $entry->id }} = true;
                                    setTimeout(() => this.copied_{{ $entry->id }} = false, 1500);
                                }
                            }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <img
                                            src="{{ $p?->thumbnail_url ?? '' }}"
                                            alt="{{ $p?->title ?? 'Produk dihapus' }}"
                                            class="h-10 w-10 flex-shrink-0 rounded object-cover"
                                        />
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                @if ($p)
                                                    <a href="{{ route('product.show', $p) }}" target="_blank" class="hover:underline">
                                                        {{ $p->title }}
                                                    </a>
                                                @else
                                                    <span class="italic text-gray-400">Produk telah dihapus</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                @if ($p)
                                                    Rp {{ number_format((float) $p->price, 0, ',', '.') }} &middot;
                                                    {{ $p->lapak?->name ?? '-' }} &middot;
                                                @endif
                                                {{ $entry->created_at->translatedFormat('d M Y, H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-shrink-0 items-center gap-2">
                                    @if ($p)
                                        <x-filament::button
                                            type="button"
                                            size="xs"
                                            color="gray"
                                            x-on:click="copyText_{{ $entry->id }}({{ \Illuminate\Support\Js::from($this->buildHistoryCopyText($p)) }})"
                                        >
                                            <span x-show="!copied_{{ $entry->id }}">Copy</span>
                                            <span x-show="copied_{{ $entry->id }}">Tersalin</span>
                                        </x-filament::button>
                                    @endif

                                    <x-filament::icon-button
                                        type="button"
                                        icon="heroicon-o-trash"
                                        color="danger"
                                        size="sm"
                                        label="Hapus riwayat"
                                        wire:click="deleteHistory({{ $entry->id }})"
                                        wire:confirm="Hapus riwayat ini?"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament::page>