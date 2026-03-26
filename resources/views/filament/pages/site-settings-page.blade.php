<x-filament::page>
    <form wire:submit.prevent="save">
        <div class="space-y-6">
            {{ $this->form }}
        </div>

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit">
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
