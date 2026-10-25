{{-- Informasi penempatan. Variabel: $internship --}}
<section class="card">
    <div class="card-head">
        <h2 class="card-title">Informasi</h2>
    </div>
    <div class="card-body">
        <dl class="kv">
            <div><dt>Mahasiswa</dt><dd>{{ $internship->student->name }}<small>{{ $internship->student->nim }}</small></dd></div>
            <div><dt>{{ $internship->isSeminar() ? "Kegiatan" : "Perusahaan" }}</dt><dd>{{ $internship->placeName() }}@if ($internship->placeDetail())<small>{{ $internship->placeDetail() }}</small>@endif</dd></div>
            <div><dt>Periode</dt><dd>{{ $internship->start_date->locale('id')->translatedFormat('j M Y') }}<small>s.d. {{ $internship->end_date->locale('id')->translatedFormat('j M Y') }}</small></dd></div>
            <div><dt>Status KP</dt><dd><x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip></dd></div>
            @if ($internship->relationLoaded('lecturer'))
                <div><dt>Dosen pembimbing</dt><dd>{{ $internship->lecturer?->listName() ?? '–' }}</dd></div>
            @endif
            @if ($internship->relationLoaded('supervisor'))
                <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->listName() ?? '–' }}</dd></div>
            @endif
        </dl>
    </div>
</section>
