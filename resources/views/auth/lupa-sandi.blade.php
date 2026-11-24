@extends('auth._card')

@section('title', 'Lupa kata sandi')

{{-- Lupa kata sandi: isi email, tautan atur ulang dikirim ke email itu. --}}
@section('card')
    <h1 class="av-title">Lupa kata sandi</h1>
    <p class="av-lead">Kami kirim tautan ke email Anda.</p>

    <form method="POST" action="{{ route('password.email') }}" class="av-form" novalidate>
        @csrf

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="av-field">
            <label for="email" class="av-label">Email</label>
            <input id="email" name="email" type="email" class="av-input" value="{{ old('email') }}" maxlength="150" required autofocus autocomplete="username" placeholder="nama@kampus.ac.id">
        </div>

        <button type="submit" class="av-submit">Kirim tautan</button>
    </form>

    <p class="av-foot"><a href="{{ route('login') }}" class="av-back">Kembali ke halaman masuk</a></p>
@endsection
