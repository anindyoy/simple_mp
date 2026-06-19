<nav class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">

        <div class="flex items-center gap-6">
            {{-- Brand --}}
            <a href="/" class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                <img src="{{ asset('img/logo-transparent.png') }}" alt="{{ $siteTitle ?? App\Models\Setting::getValue('site_title', 'Lapak Warga') }}" class="h-12 w-auto max-w-full object-contain" />
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('rules.index') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                    Peraturan
                </a>

                <a href="https://wa.me/{{ env('HP_ADMIN') }}" target="_blank" rel="noopener noreferrer"
                    class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
                    Hubungi Kami
                </a>
            </div>
        </div>

        {{-- Actions --}}
        @php $demoMode = config('app.demo_mode'); @endphp
        <div class="flex items-center gap-2 {{ $demoMode ? 'opacity-50 pointer-events-none select-none' : '' }}">
            @auth
                {{-- Member Area --}}
                <a href="/admin"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-400 dark:hover:text-gray-900 transition">
                    Area Member
                </a>

                {{-- Logout --}}
                <form action="{{ route('logout.public') }}" method="POST">
                    @csrf
                    <button type="submit" {{ $demoMode ? 'disabled' : '' }} class="px-4 py-2 text-sm font-medium border border-red-500 text-red-600 rounded-lg hover:bg-red-500 hover:text-white transition duration-200 ease-in-out">
                        Keluar
                    </button>
                </form>
            @else
                {{-- Login --}}
                <button data-modal-target="loginModal" data-modal-toggle="loginModal"
                    {{ $demoMode ? 'disabled' : '' }}
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                    Masuk
                </button>

                {{-- Register --}}
                <button data-modal-target="registerModal" data-modal-toggle="registerModal"
                    {{ $demoMode ? 'disabled' : '' }}
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-blue-600 bg-blue-600 text-white hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600 transition">
                    Daftar
                </button>
            @endauth

            {{-- Mobile menu toggle --}}
            <button id="mobileMenuToggle" class="md:hidden inline-flex items-center justify-center rounded-lg p-2 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <a href="https://wa.me/{{ env('HP_ADMIN') }}" target="_blank" rel="noopener noreferrer"
            class="block text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
            Hubungi Kami
        </a>
    </div>
</nav>

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
