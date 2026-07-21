<x-filament::page>
    <form wire:submit.prevent="save">
        <div class="space-y-6">
            {{ $this->form }}
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; align-items: center;">
            <x-filament::button type="submit">
                Simpan Perubahan
            </x-filament::button>

            @if ($lapak && $lapak->exists)
                <x-filament::button
                    tag="a"
                    href="{{ route('lapak.show', $lapak) }}"
                    target="_blank"
                    color="gray"
                    icon="heroicon-o-eye">
                    Lihat Profil Toko
                </x-filament::button>
            @endif
        </div>
    </form>
</x-filament::page>
