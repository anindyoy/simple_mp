<nav class="bg-white border-b dark:bg-gray-800 sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center px-4 py-2.5">
        <a href="/" class="text-xl font-bold text-blue-600">
            Jual Beli <span class="text-orange-500">Cimanglid</span>
        </a>

        <div class="flex items-center gap-3">
            @auth
                <a href="/admin" class="text-sm hover:text-blue-600">Member Area</a>

                <form action="{{ route('logout.public') }}" method="POST">
                    @csrf
                    <button class="text-sm text-red-600 hover:underline">Logout</button>
                </form>
            @else
                <button data-modal-toggle="loginModal" class="text-sm">Login</button>
                <button data-modal-toggle="registerModal"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                    Register
                </button>
            @endauth
        </div>
    </div>
</nav>
