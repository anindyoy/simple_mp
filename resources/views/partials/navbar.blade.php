@php
    $adminAgents = \Illuminate\Support\Facades\Cache::remember(
        'whatsapp_active_agents',
        now()->addDays(7),
        fn() => \JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent::where('active', true)->get()
    );
    $singleAdminPhone = $adminAgents->count() === 1 ? $adminAgents->first()->phone : config('app.hp_admin');
@endphp

<nav class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">

        <div class="flex items-center gap-6">
            {{-- Brand --}}
            <a href="/" class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                <img src="{{ asset('img/logo-transparent.png') }}" alt="{{ $siteTitle ?? App\Models\Setting::getValue('site_title', 'Lapak Warga') }}" class="h-8 md:h-12 w-auto max-w-full object-contain" />
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('rules.index') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                    Peraturan
                </a>

                @if ($adminAgents->count() > 1)
                    <button type="button" data-modal-target="contactAdminModal" data-modal-toggle="contactAdminModal"
                        class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                        Hubungi Kami
                    </button>
                @else
                    <a href="https://wa.me/{{ $singleAdminPhone }}" target="_blank" rel="noopener noreferrer"
                        class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                        Hubungi Kami
                    </a>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        @php $demoMode = config('app.demo_mode'); @endphp
        <div class="flex items-center gap-1.5 md:gap-2 {{ $demoMode ? 'opacity-50 pointer-events-none select-none' : '' }}">
            @auth
                {{-- Member Area --}}
                <a href="/admin"
                    class="inline-flex items-center justify-center rounded-lg px-2 py-1 md:px-4 md:py-2 text-[11px] leading-tight md:text-sm font-medium border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-400 dark:hover:text-gray-900 transition whitespace-nowrap">
                    Area Member
                </a>

                {{-- Logout --}}
                <form action="{{ route('logout.public') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar?')">
                    @csrf
                    <button type="submit" {{ $demoMode ? 'disabled' : '' }} class="px-2 py-1 md:px-4 md:py-2 text-[11px] leading-tight md:text-sm font-medium border border-red-500 text-red-600 rounded-lg hover:bg-red-500 hover:text-white transition duration-200 ease-in-out whitespace-nowrap">
                        Keluar
                    </button>
                </form>
            @else
                {{-- Login --}}
                <button data-modal-target="loginModal" data-modal-toggle="loginModal"
                    {{ $demoMode ? 'disabled' : '' }}
                    class="inline-flex items-center justify-center rounded-lg px-2 py-1 md:px-4 md:py-2 text-[11px] leading-tight md:text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition whitespace-nowrap">
                    Masuk
                </button>

                {{-- Register --}}
                <button data-modal-target="registerModal" data-modal-toggle="registerModal"
                    {{ $demoMode ? 'disabled' : '' }}
                    class="inline-flex items-center justify-center rounded-lg px-2 py-1 md:px-4 md:py-2 text-[11px] leading-tight md:text-sm font-medium border border-blue-600 bg-blue-600 text-white hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600 transition whitespace-nowrap">
                    Daftar
                </button>
            @endauth

            {{-- Mobile menu toggle --}}
            <button id="mobileMenuToggle" class="md:hidden inline-flex items-center justify-center rounded-lg p-1.5 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition" aria-label="Menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile dropdown menu --}}
    <div id="mobileMenu" class="hidden md:hidden border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
        <a href="{{ route('rules.index') }}" class="block text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
            Peraturan
        </a>

        @if ($adminAgents->count() > 1)
            <button type="button" data-modal-target="contactAdminModal" data-modal-toggle="contactAdminModal"
                class="block w-full text-left text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                Hubungi Kami
            </button>
        @else
            <a href="https://wa.me/{{ $singleAdminPhone }}" target="_blank" rel="noopener noreferrer"
                class="block text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                Hubungi Kami
            </a>
        @endif
    </div>
</nav>

@if ($adminAgents->count() > 1)
    <div id="contactAdminModal" data-modal-target="contactAdminModal" tabindex="-1" aria-hidden="true"
        class="modal-backdrop hidden fixed inset-0 z-50 bg-black/50">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full p-6" style="width: min(88vw, 26rem);">
                <div class="flex justify-between items-center border-b pb-3 mb-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hubungi Kami</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih nomor admin yang ingin dihubungi</p>
                    </div>
                    <button data-modal-hide="contactAdminModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">✕</button>
                </div>

                <div class="space-y-2">
                    @foreach ($adminAgents as $agent)
                        <a href="https://wa.me/{{ $agent->phone }}" target="_blank" rel="noopener noreferrer"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-blue-500 hover:bg-blue-50 dark:border-gray-700 dark:hover:border-blue-500 dark:hover:bg-blue-900/20">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                @if ($agent->gender === 'pria')
                                    <img src="{{ asset('img/pria.png') }}" alt="Pria" class="h-8 w-8 rounded-full object-cover" />
                                @elseif ($agent->gender === 'wanita')
                                    <img src="{{ asset('img/wanita.png') }}" alt="Wanita" class="h-8 w-8 rounded-full object-cover" />
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $agent->name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $agent->phone }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Mobile menu toggle script --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('mobileMenuToggle');
        const menu = document.getElementById('mobileMenu');

        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                menu.classList.toggle('hidden');
            });
        }
    });
</script>
