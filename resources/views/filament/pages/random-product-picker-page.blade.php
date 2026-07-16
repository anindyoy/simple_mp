<x-filament::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Klik tombol di bawah untuk memilih satu produk aktif secara acak dari seluruh lapak yang sedang tampil.
            </p>

            <x-filament::button wire:click="generate" icon="heroicon-o-sparkles">
                Generate Produk Acak
            </x-filament::button>
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
    </div>
</x-filament::page>
