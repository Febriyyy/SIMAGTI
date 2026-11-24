@extends('auth._card')

@section('title', 'Atur ulang kata sandi')

{{-- Atur ulang kata sandi dari tautan email. Variabel: token, email --}}
@section('card')
    <?php
        $eye = '<svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
            .'<svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
    ?>
    <h1 class="av-title">Atur ulang kata sandi</h1>
    <p class="av-lead">Buat kata sandi baru untuk akun Anda.</p>

    <form method="POST" action="{{ route('password.update') }}" class="av-form" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        @if ($errors->any())
            <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="av-field">
            <label for="email" class="av-label">Email</label>
            <input id="email" name="email" type="email" class="av-input" value="{{ old('email', $email) }}" maxlength="150" required autocomplete="username">
        </div>

        <div class="av-field">
            <label for="password" class="av-label">Kata sandi baru</label>
            <div class="av-pass">
                <input id="password" name="password" type="password" class="av-input" minlength="8" required autofocus autocomplete="new-password" aria-describedby="av-rules">
                <button type="button" class="av-eye" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
            </div>
        </div>

        <div class="av-field">
            <label for="password_confirmation" class="av-label">Ulangi sandi</label>
            <div class="av-pass">
                <input id="password_confirmation" name="password_confirmation" type="password" class="av-input" required autocomplete="new-password" aria-describedby="av-rules">
                <button type="button" class="av-eye" data-toggle-password="password_confirmation" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
            </div>
        </div>

        <ul class="av-rules" id="av-rules" aria-live="polite">
            <li data-rule="length">8+ karakter</li>
            <li data-rule="match">Sama</li>
        </ul>

        <button type="submit" class="av-submit">Simpan kata sandi</button>
    </form>

    <p class="av-foot"><a href="{{ route('login') }}" class="av-back">Kembali ke halaman masuk</a></p>
@endsection
