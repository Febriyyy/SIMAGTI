{{-- Utas diskusi laporan. Variabel: thread (koleksi ReportComment) --}}
@if ($thread->isNotEmpty())
    <ul class="thread rd-thread">
        @foreach ($thread as $c)
            <?php $mine = (int) $c->user_id === (int) auth()->id(); $fromLecturer = $c->user?->role === 'dospem'; ?>
            <li @class(['thread-item', 'rd-msg', 'is-mine' => $mine])>
                @if ($c->user)<x-avatar :user="$c->user" small />@endif
                <div class="thread-body">
                    <div class="comment-meta">
                        <strong>{{ $mine ? 'Anda' : $c->user?->name }}</strong>
                        <span class="muted">· {{ $fromLecturer ? 'Dosen pembimbing' : 'Mahasiswa' }} · {{ $c->created_at->locale('id')->translatedFormat('j M, H.i') }}</span>
                    </div>
                    @if ($c->body)<p class="prewrap">{{ $c->body }}</p>@endif
                    @if ($c->file)
                        <a href="{{ route('laporan.komentar.berkas', $c) }}" target="_blank" rel="noopener" @class(['rd-attach', 'is-fix' => $fromLecturer])>
                            <x-ikon name="paperclip" :size="15" />
                            <span class="min0">{{ $c->file->original_name }}</span>
                            @if ($fromLecturer)<em>Berkas koreksi</em>@endif
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
