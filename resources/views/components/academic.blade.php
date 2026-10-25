{{-- Identitas akademik mahasiswa: universitas · fakultas · prodi · angkatan. Variabel: user, compact (satu baris) --}}
@props(['user', 'compact' => false])
@php($id = $user->academicIdentity())
@if ($compact)
    <span {{ $attributes->class(['academic-line']) }} title="{{ $id['university'] }} · {{ $id['faculty'] }}">
        {{ $id['program'] ?? 'Prodi belum diisi' }}@if ($id['entry_year']) · Angkatan {{ $id['entry_year'] }}@endif
    </span>
@else
    <dl {{ $attributes->class(['kv', 'kv-tight']) }}>
        <div><dt>Universitas</dt><dd>{{ $id['university'] }}</dd></div>
        <div><dt>Fakultas</dt><dd>{{ $id['faculty'] ?? '–' }}</dd></div>
        <div><dt>Program studi</dt><dd>{{ $id['program'] ?? '–' }}</dd></div>
        <div><dt>Angkatan</dt><dd>{{ $id['entry_year'] ?? '–' }}@if ($id['semester'])<small>Semester {{ $id['semester'] }}</small>@endif</dd></div>
    </dl>
@endif
