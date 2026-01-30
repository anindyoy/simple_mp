<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Jual Beli Cimanglid')</title>

    @include('partials.meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900">

    @include('partials.navbar')

    <main>
        @yield('content')
    </main>

    @guest
        <x-auth-modal type="login" />
        <x-auth-modal type="register" />
    @endguest

    @include('partials.footer')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</body>
</html>
