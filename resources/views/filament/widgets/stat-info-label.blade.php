<span x-data="{ open: false }" class="inline-flex items-center gap-1">
    <span>{{ $label }}</span>

    <button
        type="button"
        x-on:click.stop.prevent="open = true"
        class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-semibold leading-none text-gray-500 hover:bg-gray-100 dark:border-gray-500 dark:text-gray-400 dark:hover:bg-gray-700"
        aria-label="Penjelasan {{ $label }}"
    >
        ?
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-[1000] flex items-center justify-center p-4"
        >
            <div
                x-show="open"
                x-transition.opacity
                x-on:click="open = false"
                class="fixed inset-0 bg-gray-950/50"
            ></div>

            <div
                x-show="open"
                x-transition
                x-on:click.outside="open = false"
                class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800"
            >
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $heading }}
                </h3>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ $description }}
                </p>

                <button
                    type="button"
                    x-on:click="open = false"
                    class="mt-4 w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                >
                    Mengerti
                </button>
            </div>
        </div>
    </template>
</span>
