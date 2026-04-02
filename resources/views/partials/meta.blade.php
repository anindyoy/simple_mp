@php
    $siteTitle = App\Models\Setting::getValue('site_title', 'Lapak Online Warga');
    $defaultRegion = App\Models\Setting::getValue('site_region', 'Cimanglid');
@endphp
<meta name="description" content="@yield('meta_description', 'Marketplace lokal warga ' . $defaultRegion . '. Jual beli langsung via WhatsApp & Telegram.')">
<meta name="keywords" content="@yield('meta_keywords', 'jual beli ' . strtolower($defaultRegion) . ', marketplace desa, iklan warga')">
<meta name="author" content="{{ $siteTitle }} {{ $defaultRegion }}">

<meta property="og:title" content="@yield('og_title', $siteTitle . ' ' . $defaultRegion)">
<meta property="og:description" content="@yield('og_description', 'Marketplace lokal warga ' . $defaultRegion . '.')">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
