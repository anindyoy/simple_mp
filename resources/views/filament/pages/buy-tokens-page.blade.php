<x-filament::page>
    <form wire:submit.prevent="submit">
        <div class="space-y-6">
            {{ $this->form }}

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700" x-data="{ copied: false }">
                    <div class="text-sm text-gray-500">Total Nominal</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($this->getTotalAmount()) }}
                    </div>
                    <div class="mt-3">
                        <x-filament::button
                            type="button"
                            size="sm"
                            color="gray"
                            x-on:click="navigator.clipboard.writeText('{{ $this->getTotalAmount() }}'); copied = true; setTimeout(() => copied = false, 1500)">
                            <span x-show="!copied">Copy Nominal</span>
                            <span x-show="copied">Tersalin</span>
                        </x-filament::button>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700" x-data="{ copied: false }">
                    <div class="text-sm text-gray-500">Rekening Terpilih</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Bank: {{ $this->getSelectedBankName() ?: '-' }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Atas Nama: {{ $this->getSelectedBankAccountHolder() ?: '-' }}
                    </div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100 break-all">
                        No. Rek: {{ $this->getSelectedBankAccountNumber() ?: '-' }}
                    </div>
                    @if ($this->getSelectedBankAccountNumber())
                        <div class="mt-3">
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                x-on:click="navigator.clipboard.writeText('{{ $this->getSelectedBankAccountNumber() }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                <span x-show="!copied">Copy Rekening</span>
                                <span x-show="copied">Tersalin</span>
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem;" class="gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="font-medium text-red-600 mb-3">
                Pastikan nominal transfer dan rekening tujuan sudah sesuai.
            </div>

            <x-filament::button type="submit">
                Ajukan tambah token
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
