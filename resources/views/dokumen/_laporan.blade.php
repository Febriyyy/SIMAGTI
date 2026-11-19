{{--
    Laporan KP per bab (halaman Dokumen mahasiswa) sebagai linimasa: titik per bab dihubungkan garis,
    batang progres bersegmen di kepala kartu. Variabel: chapters, canUpload
--}}
<?php
    $approved = $chapters->where('status', 'disetujui')->count();
    $total = $chapters->count();
    // Keadaan titik: kosong (belum), menunggu (ditinjau), revisi, selesai
    $node = fn ($c) => match ($c->status) {
        'disetujui' => 'done',
        'revisi' => 'revise',
        'menunggu_supervisor', 'menunggu_dosen' => 'wait',
        default => 'empty',
    };
?>
<section class="card rtl" id="laporan">
    <div class="card-head rtl-head">
        <div>
            <h2 class="card-title">Laporan KP</h2>
            <p class="card-sub">Bab bertanda <x-ikon name="building" :size="13" /> ditinjau Company Supervisor dulu</p>
        </div>
        <div class="rtl-progress" role="img" aria-label="{{ $approved }} dari {{ $total }} bab disetujui">
            <span class="rtl-segs" aria-hidden="true">
                @foreach ($chapters as $c)<i class="is-{{ $node($c) }}"></i>@endforeach
            </span>
            <span class="rtl-count"><b>{{ $approved }}</b> dari {{ $total }} disetujui</span>
        </div>
    </div>

    <ol class="rtl-list">
        @foreach ($chapters as $chapter)
            <?php $state = $node($chapter); ?>
            <li class="rtl-item is-{{ $state }}">
                <span class="rtl-node" aria-hidden="true">
                    @if ($state === 'done')<x-ikon name="check" :size="14" />@elseif ($state === 'revise')<span>!</span>@endif
                </span>
                <div class="rtl-body">
                    <div class="rtl-title">
                        <span class="sr-only">Bab {{ $chapter->position }}:</span>{{ $chapter->title }}
                        @if ($chapter->needs_supervisor)
                            <span class="rtl-co" title="Memuat informasi perusahaan: disetujui Company Supervisor sebelum ke dosen"><x-ikon name="building" :size="13" /><span class="sr-only">Ditinjau perusahaan</span></span>
                        @endif
                    </div>
                    <div class="rtl-meta">
                        @if ($chapter->file)
                            <a href="{{ route('laporan.berkas', $chapter) }}" target="_blank" rel="noopener" class="rtl-file" title="{{ $chapter->file->original_name }}"><x-ikon name="paperclip" :size="13" /><span>{{ $chapter->file->original_name }}</span></a>
                            <span class="rtl-status tone-{{ $chapter->statusTone() }}">{{ $chapter->statusLabel() }}</span>
                        @else
                            <span>Bab {{ $chapter->position }} · PDF atau Word, maks. 10 MB</span>
                        @endif
                    </div>
                    @if ($state === 'revise' && $chapter->revision_note)
                        <p class="rtl-note"><b>{{ $chapter->reviser?->role === 'supervisor' ? 'Company Supervisor' : 'Dosen pembimbing' }}</b> {{ $chapter->revision_note }}</p>
                    @endif
                </div>

                @if ($canUpload && $state !== 'done')
                    <form method="POST" action="{{ route('laporan.unggah', $chapter) }}" enctype="multipart/form-data" class="rtl-action">
                        @csrf
                        @if ($chapter->file && $state !== 'revise')
                            <label class="btn btn-sm btn-icon btn-ghost" title="Ganti berkas">
                                <x-ikon name="upload" :size="17" />
                                <input type="file" name="berkas" accept=".pdf,.docx" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Ganti berkas {{ $chapter->label() }}">
                            </label>
                        @else
                            <label @class(['btn', 'btn-sm', 'btn-primary' => $state === 'revise'])>
                                <x-ikon name="upload" :size="16" /> {{ $state === 'revise' ? 'Unggah ulang' : 'Unggah' }}
                                <input type="file" name="berkas" accept=".pdf,.docx" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Berkas {{ $chapter->label() }}">
                            </label>
                        @endif
                    </form>
                @endif
            </li>
        @endforeach
    </ol>

    <div class="card-foot rtl-foot">
        @isset($internship)
            <a href="{{ route('laporan.diskusi', $internship) }}"><x-ikon name="syllabus" :size="16" /> Buka halaman Laporan KP</a>
        @endisset
        <a href="{{ route('laporan.draf') }}"><x-ikon name="file" :size="16" /> Butuh kerangka? Buka draf laporan otomatis</a>
    </div>
</section>
