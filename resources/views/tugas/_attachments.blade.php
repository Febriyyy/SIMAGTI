{{-- Lampiran materi lama (dari versi awal, saat task masih dibuat mentor). Hanya bisa dibuka. Variabel: $task --}}
<ul class="files">
    @foreach ($task->attachments as $attachment)
        @php($file = $attachment->file)
        @continue(! $file)
        @php($ext = $file->extension())
        <li class="file">
            @php($kind = in_array($ext, ['ppt', 'pptx']) ? 'slide' : ($file->isImage() ? 'image' : 'pdf'))
            <span class="file-icon kind-{{ $kind }}"><x-ikon :name="$kind" :size="20" /></span>
            <div class="file-body">
                <a href="{{ route('tugas.lampiran.show', [$task, $attachment]) }}" class="file-name" @if ($file->opensInline()) target="_blank" rel="noopener" @endif title="{{ $file->original_name }}">{{ $file->original_name }}</a>
                <span class="file-meta">{{ \App\Models\TaskAttachment::TYPES[$ext] ?? strtoupper($ext) }} · {{ $file->sizeLabel() }} · {{ $attachment->created_at->locale('id')->translatedFormat('j M Y') }}</span>
            </div>
            <a href="{{ route('tugas.lampiran.show', [$task, $attachment, 'unduh' => 1]) }}" class="btn btn-sm btn-icon" title="Unduh" aria-label="Unduh {{ $file->original_name }}"><x-ikon name="download" :size="18" /></a>
        </li>
    @endforeach
</ul>
