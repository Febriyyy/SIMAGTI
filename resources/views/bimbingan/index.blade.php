@extends('layouts.app')

@section('title', 'Bimbingan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Bimbingan</h1>
            <p class="page-sub">Minimal {{ $min }} sesi terkonfirmasi dosen.</p>
        </div>
        @if ($internship)
            @php($confirmed = $sessions->where('status', 'confirmed')->count())
            <x-chip :tone="$confirmed >= $min ? 'green' : 'amber'">{{ $confirmed }} / {{ $min }} terkonfirmasi</x-chip>
        @endif
    </div>

    @if (! $internship)
        <div class="card"><div class="empty"><strong>Belum ada KP berjalan</strong> Log bimbingan tersedia setelah KP dimulai.</div></div>
    @else
        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Riwayat bimbingan</h2>
                            <p class="card-sub">Dengan {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }}</p>
                        </div>
                    </div>
                    @include('bimbingan._list', ['sessions' => $sessions, 'canDelete' => $canEdit])
                </section>
            </div>
            @if ($canEdit)
                <div class="col">
                    <section class="card">
                        <div class="card-head"><h2 class="card-title">Catat bimbingan</h2></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('bimbingan.store') }}" class="stack">
                                @csrf
                                <div class="field">
                                    <label class="label" for="held_on">Tanggal</label>
                                    <input id="held_on" type="date" name="held_on" class="input" value="{{ old('held_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="field">
                                    <label class="label" for="mode">Cara</label>
                                    <select id="mode" name="mode" class="select">
                                        @foreach (\App\Models\GuidanceSession::MODES as $k => $v)
                                            <option value="{{ $k }}" @selected(old('mode') === $k)>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label class="label" for="topic">Topik</label>
                                    <input id="topic" type="text" name="topic" class="input" maxlength="200" value="{{ old('topic') }}" placeholder="Contoh: Pembahasan kerangka laporan" required>
                                </div>
                                <div class="field">
                                    <label class="label" for="notes">Catatan / hasil bimbingan</label>
                                    <textarea id="notes" name="notes" class="textarea" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                                </div>
                                <div class="row-actions"><button type="submit" class="btn btn-primary"><x-ikon name="plus" :size="18" /> Simpan</button></div>
                            </form>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    @endif
@endsection
