<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Ikon Google Material Symbols; hanya ikon yang dipakai aplikasi (lihat app/Support/Icons.php) --}}
    <link href="{{ \App\Support\Icons::fontUrl() }}" rel="stylesheet">
    <script>
        // Tampilkan Material Symbols hanya setelah fontnya benar-benar siap; sebelum itu pakai ikon cadangan.
        (function () {
            var root = document.documentElement;
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
<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="brand-logo" width="168" height="51">
        </a>

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

        <div class="sidebar-foot">
            <span class="nav-label">Akun</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-item is-danger">
                    <x-ikon name="logout" />
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <span class="topbar-date"><x-ikon name="calendar" :size="16" /> {{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
            <div class="profile">
                <div class="profile-text">
                    <span class="profile-name">{{ $user->name }}</span>
                    <span class="profile-sub">{{ $user->roleLabel() }}@if ($user->nim) · {{ $user->nim }}@endif</span>
                </div>
                <x-avatar :user="$user" />
            </div>
        </header>

        <div class="container">
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
