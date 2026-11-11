{{--
    Kartu task di board. Warna kartu mengikuti warna proyek (pastel); tanpa proyek kartu putih.
    Atas: pil prioritas + kode task. Tengah: judul, progres checklist berupa titik. Bawah: label proyek/goal dan meta kecil.
    Variabel: $task, $canEdit
--}}
@php($projColor = $task->project?->color ?? 'none')
@php($checkTotal = (int) ($task->checklist_count ?? 0))
@php($checkDone = (int) ($task->checklist_done_count ?? 0))
<article @class(['kcard', 'proj-'.$projColor, 'is-done' => $task->isDone(), 'is-overdue' => $task->isOverdue()])
         @if ($canEdit) draggable="true" data-task="{{ $task->id }}" data-move-url="{{ route('kegiatan.tugas.move', $task) }}" @endif>
    <div class="kcard-top">
        <span class="prio-pill prio-{{ $task->priority }}" title="Prioritas {{ $task->priorityLabel() }}">
            <x-ikon name="flag" :size="12" :fill="in_array($task->priority, ['urgent', 'high'], true)" /> {{ $task->priorityLabel() }}<span class="sr-only"> (prioritas)</span>
        </span>
        <span class="kcard-key">{{ $task->key() }}</span>
    </div>

    <a href="{{ route('tugas.show', $task) }}" class="kcard-title">{{ $task->title }}</a>

    @if ($checkTotal > 0)
        @php($dots = min($checkTotal, 10))
        @php($filled = $checkTotal > 10 ? (int) round($checkDone / $checkTotal * 10) : $checkDone)
        <div class="kcard-progress" title="Checklist {{ $checkDone }}/{{ $checkTotal }}">
            <div class="kcard-progress-top"><span>Checklist</span><b>{{ $checkDone }}/{{ $checkTotal }}</b></div>
            <div class="kcard-dots" aria-hidden="true">
                @for ($d = 0; $d < $dots; $d++)<i @class(['is-on' => $d < $filled])></i>@endfor
            </div>
        </div>
    @endif

    @if ($task->project || $task->goal || $task->labels)
        <div class="kcard-tags">
            @if ($task->project)
                <span class="tag tag-proj" title="Proyek {{ $task->project->name }}">{{ $task->project->name }}</span>
            @endif
            @if ($task->goal)
                <span class="tag tag-outline" title="Goal: {{ $task->goal->title }}">{{ \Illuminate\Support\Str::limit($task->goal->title, 24) }}</span>
            @endif
            @foreach ((array) $task->labels as $label)
                <span class="tag tag-gray">{{ $label }}</span>
            @endforeach
        </div>
    @endif

    <div class="kcard-meta">
        @if ($task->due_date)
            <span @class(['meta', 'is-late' => $task->isOverdue()]) title="Tenggat"><x-ikon name="calendar" :size="14" /> {{ $task->due_date->locale('id')->translatedFormat('j M') }}</span>
        @endif
        @if (($task->evidences_count ?? 0) > 0)
            <span class="meta" title="Bukti"><x-ikon name="paperclip" :size="14" /> {{ $task->evidences_count }}</span>
        @endif
        @if (($task->comments_count ?? 0) > 0)
            <span class="meta" title="Komentar"><x-ikon name="chat" :size="14" /> {{ $task->comments_count }}</span>
        @endif
    </div>
</article>
