<nav class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">

        {{-- Brand --}}
        <a href="/" class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
            Jual Beli <span class="text-orange-500">Cimanglid</span>
        </a>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            @auth
                {{-- Member Area --}}
                <a href="/admin" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-400 dark:hover:text-gray-900 transition">
                    Member Area
                </a>

                {{-- Logout --}}
                <form action="{{ route('logout.public') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium border border-red-500 text-red-600 rounded-lg hover:bg-red-500 hover:text-white transition duration-200 ease-in-out">
                        Logout
                    </button>
                </form>
            @else
                {{-- Login --}}
                <button data-modal-target="loginModal" data-modal-toggle="loginModal" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                    Login
                </button>

                {{-- Register --}}
                <button data-modal-target="registerModal" data-modal-toggle="registerModal" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-blue-600 bg-blue-600 text-white hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600 transition">
                    Register
                </button>
            @endauth
        </div>
    </div>
</nav>
