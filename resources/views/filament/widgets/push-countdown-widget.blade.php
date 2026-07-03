<x-filament-widgets::widget>
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
        }"
        class="fi-wi-stats-overview-stat relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
    >
        <div class="fi-wi-stats-overview-stat-content grid gap-y-2">
            <div class="fi-wi-stats-overview-stat-label-ctn flex items-center gap-x-2">
                <x-filament::icon
                    icon="heroicon-o-banknotes"
                    class="fi-icon h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500"
                />

                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                    Jumlah Token
                </span>
            </div>

            <div
                @class([
                    'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight',
                    'text-success-600 dark:text-success-400' => (int) $pushTokens > 0,
                    'text-danger-600 dark:text-danger-400' => (int) $pushTokens === 0,
                ])
            >
                {{ (int) $pushTokens }}
            </div>

            <div class="fi-wi-stats-overview-stat-description flex items-center text-sm text-gray-500 dark:text-gray-400">
                <template x-if="pushTokens === 0">
                    <span class="font-medium text-danger-600 dark:text-danger-400">
                        Token habis. Isi token dulu untuk bisa membuat atau mengangkat produk.
                    </span>
                </template>

                <template x-if="remaining > 0 && pushTokens > 0">
                    <span>
                        Sisa waktu untuk membuat/mengangkat produk:
                        <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="format(remaining)"></span>

                        @if ($nextPushAtLabel)
                            <span class="block text-xs text-gray-400 dark:text-gray-500">
                                ({{ $nextPushAtLabel }})
                            </span>
                        @endif
                    </span>
                </template>

                <template x-if="remaining === 0 && pushTokens > 0">
                    <span class="font-medium text-success-600 dark:text-success-400">
                        Kamu sudah bisa langsung membuat atau mengangkat produk sekarang.
                    </span>
                </template>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
