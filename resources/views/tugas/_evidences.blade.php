{{-- Bukti pengerjaan: daftar + form kirim. Variabel: $task, $isStudent, $isMentor, $locked --}}
@php($canSubmit = $isStudent && ! $locked)

@if ($task->evidences->isEmpty())
    <p class="muted">{{ $isMentor ? 'Mahasiswa belum mengirim bukti.' : 'Belum ada bukti yang dikirim.' }}</p>
@else
    <ul class="files">
        @foreach ($task->evidences->sortByDesc('created_at') as $evidence)
            @continue($evidence->isFile() && ! $evidence->file)
            <li class="file">
                <span class="file-icon kind-{{ $evidence->icon() }}"><x-ikon :name="$evidence->icon()" :size="20" /></span>
                <div class="file-body">
                    <a href="{{ $evidence->target() }}" class="file-name" title="{{ $evidence->label() }}"
                       @if (! $evidence->isFile() || $evidence->file->opensInline()) target="_blank" rel="noopener noreferrer" @endif>{{ $evidence->label() }}</a>
                    <span class="file-meta">
                        @if ($evidence->isFile())
                            {{ strtoupper($evidence->file->extension()) }} · {{ $evidence->file->sizeLabel() }}
                        @else
                            Link
                        @endif
                        · {{ $evidence->created_at->locale('id')->translatedFormat('j M, H:i') }}
                    </span>
                </div>
                @if ($evidence->isFile())
                    <a href="{{ route('tugas.bukti.show', [$task, $evidence, 'unduh' => 1]) }}" class="btn btn-sm btn-icon" title="Unduh" aria-label="Unduh {{ $evidence->label() }}"><x-ikon name="download" :size="18" /></a>
                @endif
                @if ($canSubmit && (int) $evidence->submitted_by === (int) auth()->id())
                    <form method="POST" action="{{ route('tugas.bukti.destroy', [$task, $evidence]) }}" onsubmit="return confirm('Hapus bukti ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="Hapus" aria-label="Hapus {{ $evidence->label() }}"><x-ikon name="delete" :size="18" /></button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@endif

@if ($canSubmit)
    <form method="POST" action="{{ route('tugas.bukti', $task) }}" enctype="multipart/form-data" class="stack evidence-form">
        @csrf
        @method('PATCH')
        <div>
            <label for="evidence_url" class="label">Link (opsional)</label>
            <input id="evidence_url" type="url" name="evidence_url" class="input" placeholder="https://github.com/... atau link Google Drive" value="{{ old('evidence_url') }}">
        </div>
        @include('tugas._upload', [
            'upName' => 'berkas',
            'upLabel' => 'Berkas (opsional)',
            'upHint' => 'Foto, PDF, Word, Excel, PowerPoint, ZIP/RAR/7Z, atau video MP4',
            'upAccept' => \App\Models\TaskEvidence::acceptList(),
            'upMax' => \App\Models\TaskEvidence::MAX_FILES,
            'upMb' => \App\Models\TaskEvidence::MAX_KB / 1024,
        ])
        <div class="row-actions"><button type="submit" class="btn btn-primary"><x-ikon name="send" :size="18" /> Kirim bukti</button></div>
    </form>
@endif
