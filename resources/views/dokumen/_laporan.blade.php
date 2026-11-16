{{-- Laporan KP per bab (halaman Dokumen mahasiswa). Variabel: chapters, canUpload --}}
@php($approved = $chapters->where('status', 'disetujui')->count())
<section class="card" id="laporan">
    <div class="card-head">
        <div>
            <h2 class="card-title">Laporan KP per bab</h2>
            <p class="card-sub">Bab bertanda <x-ikon name="building" :size="13" /> ditinjau Company Supervisor dulu</p>
        </div>
        <x-chip :tone="$approved === $chapters->count() ? 'green' : 'gray'">{{ $approved }}/{{ $chapters->count() }} disetujui</x-chip>
    </div>
    <ol class="chapter-list">
        @foreach ($chapters as $chapter)
            <li @class(['chapter', 'is-approved' => $chapter->status === 'disetujui'])>
                <span class="chapter-num" aria-hidden="true">@if ($chapter->status === 'disetujui')<x-ikon name="check" :size="16" />@else{{ $chapter->position }}@endif</span>
                <div class="min0 grow">
                    <div class="chapter-title">
                        <span class="sr-only">Bab {{ $chapter->position }}:</span>{{ $chapter->title }}
                        @if ($chapter->needs_supervisor)<span class="chapter-co" title="Memuat informasi perusahaan: disetujui Company Supervisor sebelum ke dosen"><x-ikon name="building" :size="14" /><span class="sr-only">Ditinjau perusahaan</span></span>@endif
                    </div>
                    <div class="chapter-sub">
                        @if ($chapter->file)
                            <a href="{{ route('laporan.berkas', $chapter) }}" target="_blank" rel="noopener" class="truncate" title="{{ $chapter->file->original_name }}">{{ $chapter->file->original_name }}</a>
                            <span>versi {{ $chapter->version }} · {{ $chapter->submitted_at?->locale('id')->translatedFormat('j M') }}</span>
                        @else
                            <span>PDF atau Word, maks. 10 MB</span>
                        @endif
                    </div>
                    @if ($chapter->status === 'revisi' && $chapter->revision_note)
                        <p class="chapter-note"><b>{{ $chapter->reviser?->role === 'supervisor' ? 'Company Supervisor' : 'Dosen pembimbing' }}:</b> {{ $chapter->revision_note }}</p>
                    @endif
                </div>
                @if ($chapter->status === 'belum')
                    <span class="chapter-state" aria-hidden="true"></span>
                @else
                    <x-chip :tone="$chapter->statusTone()" class="chapter-state">{{ $chapter->statusLabel() }}</x-chip>
                @endif
                @if ($canUpload && $chapter->status !== 'disetujui')
                    <form method="POST" action="{{ route('laporan.unggah', $chapter) }}" enctype="multipart/form-data" class="chapter-upload">
                        @csrf
                        <label class="btn btn-sm {{ $chapter->status === 'revisi' ? 'btn-primary' : '' }}">
                            <x-ikon name="upload" :size="16" /> {{ $chapter->file ? 'Ganti' : 'Unggah' }}
                            <input type="file" name="berkas" accept=".pdf,.docx" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Berkas {{ $chapter->label() }}">
                        </label>
                    </form>
                @endif
            </li>
        @endforeach
    </ol>
    <div class="card-foot chapter-foot">
        <a href="{{ route('laporan.draf') }}"><x-ikon name="file" :size="16" /> Butuh kerangka? Buka draf laporan otomatis</a>
    </div>
</section>
