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
</head>

<body class="bg-gray-50 dark:bg-gray-900">

    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50">
        <div class="flex flex-wrap justify-between items-center container mx-auto">
            <a href="/" class="flex items-center">
                <span class="self-center text-xl font-bold whitespace-nowrap dark:text-white text-blue-600 tracking-tight">
                    Jual Beli <span class="text-orange-500">Cimanglid</span>
                </span>
            </a>
            <div class="flex items-center lg:order-2">
                <a href="#"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 lg:px-5 lg:py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Pasang
                    Iklan</a>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

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
