<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Ikon Google Material Symbols; hanya ikon yang dipakai aplikasi (lihat app/Support/Icons.php) --}}
    <link href="{{ \App\Support\Icons::fontUrl() }}" rel="stylesheet">
    <script>
        // Tampilkan Material Symbols hanya setelah fontnya benar-benar siap; sebelum itu pakai ikon cadangan.
        (function () {
            var root = document.documentElement;
            root.classList.add('js');
            // Animasi masuk halaman hanya bila halaman terlihat saat dimuat (tab latar belakang langsung tampil penuh).
            if (document.visibilityState === 'visible') root.classList.add('motion');
            try { if (sessionStorage.getItem('ms-ready') === '1') root.classList.add('ms-ready'); } catch (e) {}
            if (!document.fonts || !document.fonts.load) return;
            document.fonts.load('24px "Material Symbols Rounded"', 'task_alt').then(function (fonts) {
                var ok = fonts && fonts.length > 0;
                root.classList.toggle('ms-ready', ok);
                try { sessionStorage.setItem('ms-ready', ok ? '1' : '0'); } catch (e) {}
            }, function () { root.classList.remove('ms-ready'); });
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body>
@php($user = auth()->user())
<a href="#konten" class="skip-link">Lewati ke konten</a>
<div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="Menu">
        <div class="brand">
            <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="brand-logo" width="168" height="51"></a>
            <button type="button" class="drawer-close" data-drawer-close aria-label="Tutup menu"><x-ikon name="close" :size="20" /></button>
        </div>

        <span class="nav-label">Menu</span>
        <nav class="nav" aria-label="Menu utama">
            @foreach (\App\Support\Menu::for($user) as $item)
                @php($active = request()->routeIs(...(array) $item['active']))
                <a href="{{ route($item['route']) }}" class="nav-item {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif>
                    <x-ikon :name="$item['icon']" :fill="$active" :size="20" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Profil di bawah sidebar: menu akun (ganti kata sandi, keluar) terbuka ke atas --}}
        <div class="sidebar-foot">
            <details class="account" @if (request()->routeIs('akun.sandi')) open @endif>
                <summary class="account-btn">
                    <x-avatar :user="$user" />
                    <span class="account-text">
                        <span class="account-name" title="{{ $user->name }}">{{ $user->listName() }}</span>
                        <span class="account-role">{{ $user->roleLabel() }}</span>
                    </span>
                    <x-ikon name="expand" :size="18" class="account-caret" />
                </summary>
                <div class="account-menu">
                    @php($pwActive = request()->routeIs('akun.sandi'))
                    <a href="{{ route('akun.sandi') }}" class="nav-item {{ $pwActive ? 'is-active' : '' }}" @if ($pwActive) aria-current="page" @endif>
                        <x-ikon name="lock" :size="18" />
                        <span>Ganti kata sandi</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-item is-danger">
                            <x-ikon name="logout" :size="18" />
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </aside>

    <div class="drawer-backdrop" data-drawer-close></div>

    <main class="main">
        @php($menuNow = collect(\App\Support\Menu::for($user))->first(fn ($m) => request()->routeIs(...(array) $m['active'])))
        @php($pageTitle = trim($__env->yieldContent('title', 'Dashboard')))
        <header class="topbar">
            <button type="button" class="menu-btn" data-drawer-open aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"><x-ikon name="menu" :size="22" /></button>
            <a href="{{ route('dashboard') }}" class="topbar-brand"><img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="112" height="34"></a>
            {{-- Letak halaman: menu yang sedang dibuka, lalu judul halaman bila berbeda --}}
            <nav class="crumbs" aria-label="Letak halaman">
                @if ($menuNow)
                    <a href="{{ route($menuNow['route']) }}" class="crumb-root"><x-ikon :name="$menuNow['icon']" :size="18" /> {{ $menuNow['label'] }}</a>
                    @if ($pageTitle !== $menuNow['label'] && ! str_starts_with($pageTitle, 'Dashboard'))
                        <x-ikon name="next" :size="16" class="crumb-sep" />
                        <span class="crumb-now" aria-current="page">{{ $pageTitle }}</span>
                    @endif
                @else
                    <span class="crumb-now" aria-current="page">{{ $pageTitle }}</span>
                @endif
            </nav>
            <div class="topbar-right">
                <span class="topbar-date">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
                <x-bell />
                <x-avatar :user="$user" class="topbar-avatar" />
            </div>
        </header>

        <div class="container" id="konten" tabindex="-1">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
<script src="{{ asset('js/charts.js') }}?v={{ filemtime(public_path('js/charts.js')) }}" defer></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
