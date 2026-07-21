<x-filament-widgets::widget>
    <div class="space-y-2">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Produk Aktif</h4>

        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full text-start">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Periode</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Produk Baru</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total Aktif (Kumulatif)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($series['labels'] as $index => $label)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-3 py-2 text-center text-sm text-gray-700 dark:text-gray-200">{{ $label }}</td>
                            <td class="px-3 py-2 text-center text-sm text-gray-700 dark:text-gray-200">{{ $series['new'][$index] }}</td>
                            <td class="px-3 py-2 text-center text-sm font-medium text-gray-950 dark:text-white">{{ $series['cumulative'][$index] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada data untuk rentang periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
