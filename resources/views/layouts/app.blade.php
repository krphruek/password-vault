<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Password Vault')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    @vite(['resources/css/UIlogin.css'])
    @yield('styles')
</head>

<body class="user-role-{{ Auth::user()->role ?? '' }}">
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    @include('partials.sidebar')

    <div class="main">
        @include('partials.topbar')
        @yield('content') {{-- globals <script> อยู่ในนี้ --}}
    </div>

    @yield('scripts') {{-- dashboard.js โหลดหลัง globals --}}
</body>