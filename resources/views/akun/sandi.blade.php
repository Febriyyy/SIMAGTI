@extends('layouts.app')

@section('title', 'Ganti kata sandi')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Ganti kata sandi</h1>
            <p class="page-sub">Minimal 8 karakter, berisi huruf dan angka.</p>
        </div>
    </div>

    @if ($forced)
        <div class="alert alert-warning" role="status">
            Ganti kata sandi sementara untuk melanjutkan.
        </div>
    @endif

    <section class="card narrow-card">
        <form method="POST" action="{{ route('akun.sandi.update') }}" class="card-body pt stack" novalidate>
            @csrf
            @method('PUT')
            <div class="field">
                <label class="label" for="current_password">{{ $forced ? 'Kata sandi sementara' : 'Kata sandi saat ini' }}</label>
                <input id="current_password" name="current_password" type="password" class="input" required autocomplete="current-password">
            </div>
            <div class="field">
                <label class="label" for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" class="input" required minlength="8" autocomplete="new-password" aria-describedby="password-hint">
                <p class="hint" id="password-hint">Berbeda dari kata sandi saat ini.</p>
            </div>
            <div class="field">
                <label class="label" for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <p class="hint">Perangkat lain otomatis keluar.</p>
                <div class="row-actions"><button type="submit" class="btn btn-primary">Simpan kata sandi</button></div>
            </div>
        </form>
    </section>
@endsection
