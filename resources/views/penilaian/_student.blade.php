{{-- Informasi penempatan. Variabel: $internship --}}
<section class="card">
    <div class="card-head">
        <h2 class="card-title">Informasi</h2>
    </div>
    <div class="card-body">
        <dl class="kv">
            <div><dt>Mahasiswa</dt><dd>{{ $internship->student->name }}<small>{{ $internship->student->nim }}</small></dd></div>
            <div><dt>Perusahaan</dt><dd>{{ $internship->company->name }}</dd></div>
            <div><dt>Periode</dt><dd>{{ $internship->start_date->locale('id')->translatedFormat('j M Y') }}<small>s.d. {{ $internship->end_date->locale('id')->translatedFormat('j M Y') }}</small></dd></div>
            <div><dt>Status KP</dt><dd><x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip></dd></div>
            @if ($internship->relationLoaded('mentor'))
                <div><dt>Mentor</dt><dd>{{ $internship->mentor?->name ?? '–' }}</dd></div>
            @endif
            @if ($internship->relationLoaded('supervisor'))
                <div><dt>Ketua divisi</dt><dd>{{ $internship->supervisor?->name ?? '–' }}</dd></div>
            @endif
        </dl>
    </div>
</section>
