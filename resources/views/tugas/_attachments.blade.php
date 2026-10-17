{{-- Daftar lampiran materi task. Variabel: $task, $canManage (mentor dan KP belum dikunci) --}}
@if ($task->attachments->isEmpty())
    <p class="muted">{{ $canManage ? 'Belum ada lampiran. Tambahkan materi seperti slide, panduan PDF, atau gambar contoh.' : 'Mentor tidak melampirkan materi untuk task ini.' }}</p>
@else
    <ul class="files">
        @foreach ($task->attachments as $attachment)
            @php($file = $attachment->file)
            @continue(! $file)
            @php($ext = $file->extension())
            <li class="file">
                <span @class(['file-icon', 'file-'.(in_array($ext, ['ppt', 'pptx']) ? 'ppt' : ($file->isImage() ? 'img' : 'pdf'))])>{{ strtoupper($ext) }}</span>
                <div class="file-body">
                    <a href="{{ route('tugas.lampiran.show', [$task, $attachment]) }}" class="file-name" @if ($file->opensInline()) target="_blank" rel="noopener" @endif title="{{ $file->original_name }}">{{ $file->original_name }}</a>
                    <span class="file-meta">{{ \App\Models\TaskAttachment::TYPES[$ext] ?? strtoupper($ext) }} · {{ $file->sizeLabel() }} · {{ $attachment->created_at->locale('id')->translatedFormat('j M Y') }}</span>
                </div>
                <a href="{{ route('tugas.lampiran.show', [$task, $attachment, 'unduh' => 1]) }}" class="btn btn-sm" title="Unduh">Unduh</a>
                @if ($canManage)
                    <form method="POST" action="{{ route('mentor.tugas.lampiran.destroy', [$task, $attachment]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost-danger" title="Hapus lampiran">Hapus</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@endif

@if ($canManage && $task->attachments->count() < \App\Models\TaskAttachment::MAX_FILES)
    <form method="POST" action="{{ route('mentor.tugas.lampiran.store', $task) }}" enctype="multipart/form-data" class="stack" style="margin-top: 16px">
        @csrf
        @include('tugas._upload')
        <div><button type="submit" class="btn btn-primary btn-sm">Unggah lampiran</button></div>
    </form>
@endif
