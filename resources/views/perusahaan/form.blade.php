@extends('layouts.app')

@section('title', $company ? 'Ubah perusahaan' : 'Tambah perusahaan')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('perusahaan.index') }}" class="back"><x-ikon name="back" :size="16" /> Perusahaan</a>
            <h1 class="page-title">{{ $company ? 'Ubah perusahaan' : 'Tambah perusahaan' }}</h1>
        </div>
    </div>

    <section class="card">
        <div class="card-body">
            <form method="POST" action="{{ $company ? route('perusahaan.update', $company) : route('perusahaan.store') }}" class="form-grid">
                @csrf
                @if ($company)
                    @method('PATCH')
                @endif

                <div class="span-all">
                    <label for="name" class="label">Nama perusahaan</label>
                    <input id="name" name="name" type="text" class="input" value="{{ old('name', $company?->name) }}" maxlength="150" required>
                </div>
                <div>
                    <label for="industry" class="label">Bidang usaha</label>
                    <input id="industry" name="industry" type="text" class="input" value="{{ old('industry', $company?->industry) }}" maxlength="100">
                </div>
                <div>
                    <label for="city" class="label">Kota</label>
                    <input id="city" name="city" type="text" class="input" value="{{ old('city', $company?->city) }}" maxlength="100">
                </div>
                <div class="span-all">
                    <label for="address" class="label">Alamat</label>
                    <textarea id="address" name="address" class="textarea" maxlength="500">{{ old('address', $company?->address) }}</textarea>
                </div>
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" class="input" value="{{ old('email', $company?->email) }}" maxlength="150">
                </div>
                <div>
                    <label for="phone" class="label">Telepon</label>
                    <input id="phone" name="phone" type="text" class="input" value="{{ old('phone', $company?->phone) }}" maxlength="20">
                </div>
                <div class="span-all">
                    <label for="website" class="label">Website</label>
                    <input id="website" name="website" type="url" class="input" value="{{ old('website', $company?->website) }}" maxlength="200" placeholder="https://">
                </div>
                <div class="span-all">
                    <label for="divisions" class="label">Tambah divisi</label>
                    <textarea id="divisions" name="divisions" class="textarea" maxlength="2000" placeholder="Satu divisi per baris">{{ old('divisions') }}</textarea>
                    @if ($company && $company->divisions->isNotEmpty())
                        <p class="hint">Divisi saat ini: {{ $company->divisions->pluck('name')->implode(', ') }}</p>
                    @endif
                </div>
                <div class="span-all">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </section>
@endsection
