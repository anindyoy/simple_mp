<div class="fi-topbar-item flex items-center gap-2">

    {{-- Tombol Home --}}
    <a
        href="/"
        target="_blank"
        class="fi-btn fi-btn-color-gray fi-btn-size-sm flex items-center gap-1">
        <x-heroicon-o-home class="w-4 h-4 shrink-0" />
        <span class="text-[10px] sm:text-sm">Halaman Utama</span>
    </a>

    {{-- Tombol Tutorial --}}
    @if ($hasTutorial)
        <button
            x-data
            x-on:click="$dispatch('open-tutorial-modal')"
            class="fi-btn fi-btn-size-sm flex items-center gap-1 text-white transition"
            style="background-color: #3F9AAE;"
            onmouseover="this.style.backgroundColor='#79C9C5'"
            onmouseout="this.style.backgroundColor='#3F9AAE'">
            <x-heroicon-o-book-open class="w-4 h-4 shrink-0" />
            <span class="text-[10px] sm:text-sm">Tutorial</span>
        </button>
    @endif

</div>

@include('filament.tutorial.modal')
