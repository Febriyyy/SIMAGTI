<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}">
</head>
<body>
@php($user = auth()->user())
<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">KP</span>
            <span class="brand-name">KP Monitor</span>
        </a>

        <span class="nav-label">Menu</span>
        <nav class="nav" aria-label="Menu utama">
            @foreach (\App\Support\Menu::for($user) as $item)
                @php($active = request()->routeIs(...(array) $item['active']))
                <a href="{{ route($item['route']) }}" class="nav-item {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="sidebar-foot">
            <span class="nav-label">Akun</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-item">
                    <x-icon name="logout" />
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <span class="topbar-date">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
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
</body>
</html>
