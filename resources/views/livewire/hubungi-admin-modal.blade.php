<div x-data="{ open: false }">
    <button
        x-on:click="open = true"
        type="button"
        class="filament-sidebar-item group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 focus:bg-gray-100 dark:hover:bg-white/5 dark:focus:bg-white/5"
    >
        <x-filament::icon
            icon="heroicon-s-chat-bubble-left"
            class="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500"
        />
        <span>
            Hubungi Admin
        </span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-on:keydown.window.escape="open = false"
        x-on:click.away="open = false"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center"
    >
        {{-- Overlay --}}
        <div
            x-show="open"
            x-on:click="open = false"
            class="fixed inset-0 bg-gray-500/50 dark:bg-gray-900/80"
        ></div>

        {{-- Modal --}}
        <div
            x-show="open"
            x-trap.noscroll="open"
            class="relative z-10 mx-auto w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800"
        >
            <div class="mb-4 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Hubungi Admin</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih nomor admin yang ingin dihubungi</p>
            </div>

            <div class="space-y-2">
                @php
                    $agents = \Illuminate\Support\Facades\Cache::remember(
                        'whatsapp_active_agents',
                        now()->addDays(7),
                        fn() => \JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent::where('active', true)->get()
                    );
                @endphp

                @forelse ($agents as $agent)
                    <a
                        href="https://wa.me/{{ $agent->phone }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-primary-500 hover:bg-primary-50 dark:border-gray-700 dark:hover:border-primary-500 dark:hover:bg-primary-900/20"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                            <x-filament::icon
                                icon="heroicon-s-chat-bubble-left-right"
                                class="h-5 w-5"
                            />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if ($agent->gender === 'pria')
                                    <x-filament::icon icon="heroicon-s-user" class="inline h-4 w-4 text-blue-500" />
                                @elseif ($agent->gender === 'wanita')
                                    <x-filament::icon icon="heroicon-s-user" class="inline h-4 w-4 text-pink-500" />
                                @endif
                                {{ $agent->name }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $agent->phone }}</span>
                        </div>
                        <div class="ml-auto">
                            <x-filament::icon
                                icon="heroicon-s-arrow-top-right-on-square"
                                class="h-4 w-4 text-gray-400"
                            />
                        </div>
                    </a>
                @empty
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada nomor admin yang tersedia</p>
                @endforelse
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    x-on:click="open = false"
                    type="button"
                    class="filament-button filament-button-size-sm inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition duration-75 hover:border-gray-400 hover:text-gray-800 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-70 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:border-gray-500 dark:hover:text-gray-100"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>