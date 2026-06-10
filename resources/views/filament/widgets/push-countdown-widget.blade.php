<x-filament-widgets::widget>
    <x-filament::section>
        <div
            wire:key="push-countdown-{{ $refreshNonce }}"
            wire:poll.30s
            x-data="{
                remaining: {{ max(0, (int) $remainingSeconds) }},
                pushTokens: {{ (int) $pushTokens }},
                format(seconds) {
                    const hours = String(Math.floor(seconds / 3600)).padStart(2, '0')
                    const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0')
                    const secs = String(seconds % 60).padStart(2, '0')

                    return `${hours}:${minutes}:${secs}`
                },
                tick() {
                    if (this.remaining > 0) {
                        this.remaining -= 1
                    }
                },
                init() {
                    setInterval(() => this.tick(), 1000)
                }
            }">
            <div class="text-sm text-gray-700 dark:text-gray-200">
                Jumlah Token:
                <span @class([
                    'font-semibold',
                    'text-success-600 dark:text-success-400' => (int) $pushTokens > 0,
                    'text-danger-600 dark:text-danger-400' => (int) $pushTokens === 0,
                ])>{{ (int) $pushTokens }}</span>
            </div>

            <div class="mt-2 text-gray-600 dark:text-gray-300">
                <template x-if="pushTokens === 0">
                    <div class="mb-2 font-semibold text-danger-600 dark:text-danger-400">
                        Token habis. Isi token dulu untuk bisa membuat atau mengangkat produk.
                    </div>
                </template>

                <template x-if="remaining > 0 && pushTokens > 0">
                    <span>
                        Sisa waktu untuk membuat atau mengangkat produk selanjutnya:
                        <span class="font-semibold" x-text="format(remaining)"></span>

                        @if ($nextPushAtLabel)
                            <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                ({{ $nextPushAtLabel }})
                            </span>
                        @endif
                    </span>
                </template>

                <template x-if="remaining === 0 && pushTokens > 0">
                    <span class="font-semibold text-success-600 dark:text-success-400">
                        Kamu sudah bisa langsung membuat produk baru atau mengangkat produk sekarang.
                    </span>
                </template>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
