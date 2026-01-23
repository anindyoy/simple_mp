<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jual Beli Cimanglid')</title>

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', 'Marketplace lokal warga Cimanglid. Jual beli langsung via WhatsApp & Telegram.')">
    <meta name="keywords" content="@yield('meta_keywords', 'jual beli cimanglid, marketplace desa, iklan warga, jual barang lokal')">
    <meta name="author" content="Jual Beli Cimanglid">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', 'Jual Beli Cimanglid')">
    <meta property="og:description" content="@yield('og_description', 'Marketplace lokal warga Cimanglid.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />

    <style>
        /* Smooth Modal Animations */
        .modal-backdrop {
            transition: opacity 300ms ease-in-out;
        }

        .modal-backdrop.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-backdrop:not(.hidden) {
            opacity: 1;
        }

        .modal-content {
            transition: transform 300ms ease-out, opacity 300ms ease-out;
        }

        .modal-backdrop.hidden .modal-content {
            transform: scale(0.95) translateY(-20px);
            opacity: 0;
        }

        .modal-backdrop:not(.hidden) .modal-content {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">

    {{-- Navbar --}}
    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50">
        <div class="flex flex-wrap justify-between items-center container mx-auto">
            <a href="/" class="flex items-center">
                <span class="self-center text-xl font-bold whitespace-nowrap dark:text-white text-blue-600 tracking-tight">
                    Jual Beli <span class="text-orange-500">Cimanglid</span>
                </span>
            </a>
            <div class="flex items-center lg:order-2 gap-2">
                @auth
                    <a href="/admin"
                        class="text-sm font-medium text-gray-700 hover:text-blue-600">
                        Member Area
                    </a>

                    <form action="{{ route('logout.public') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="text-sm font-medium text-red-600 hover:underline">
                            Logout
                        </button>
                    </form>
                @else
                    <button
                        data-modal-target="loginModal"
                        data-modal-toggle="loginModal"
                        class="text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors">
                        Login
                    </button>

                    <button
                        data-modal-target="registerModal"
                        data-modal-toggle="registerModal"
                        class="text-sm font-medium text-white bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Register
                    </button>

                @endauth
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    {{-- Register Modal --}}
    <div id="registerModal" tabindex="-1" aria-hidden="true"
        class="modal-backdrop hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full h-full bg-black/50">

        <div class="relative p-4 w-full max-w-md h-full flex items-center">
            <div class="modal-content relative bg-white rounded-lg shadow-xl dark:bg-gray-800 w-full">
                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Daftar Akun
                    </h3>
                    <button type="button"
                        class="text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-lg p-1.5 transition-colors"
                        data-modal-hide="registerModal">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('register.public') }}" class="p-4 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                        <input type="text" name="name" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" name="email" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" name="password" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Daftar
                    </button>

                    <p class="text-sm text-center text-gray-500">
                        Sudah punya akun?
                        <button type="button"
                            data-modal-hide="registerModal"
                            data-modal-target="loginModal"
                            data-modal-toggle="loginModal"
                            class="text-blue-600 hover:underline transition-all">
                            Login
                        </button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    {{-- Login Modal --}}
    <div id="loginModal" tabindex="-1" aria-hidden="true"
        class="modal-backdrop hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full h-full bg-black/50">

        <div class="relative p-4 w-full max-w-md h-full flex items-center">
            <div class="modal-content relative bg-white rounded-lg shadow-xl dark:bg-gray-800 w-full">
                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Login
                    </h3>
                    <button type="button"
                        class="text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-lg p-1.5 transition-colors"
                        data-modal-hide="loginModal">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('login.public') }}" class="p-4 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" name="email" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm mb-1 text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" name="password" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember" class="rounded">
                        <label for="remember" class="text-sm text-gray-700 dark:text-gray-300">Ingat saya</label>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="p-4 bg-white md:p-8 lg:p-10 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="mx-auto max-w-screen-xl text-center">
            <span class="flex justify-center items-center text-xl font-bold text-gray-900 dark:text-white uppercase tracking-widest">
                JUAL BELI CIMANGLID
            </span>
            <p class="my-6 text-gray-500 dark:text-gray-400 text-sm italic">Warga Bantu Warga - Transaksi via WhatsApp & Telegram.</p>
            <span class="text-xs text-gray-400 sm:text-center">© 2026 Jual Beli Cimanglid.</span>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</body>

</html>