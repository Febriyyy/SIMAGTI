{{-- Kartu task di board. Variabel: $task, $canEdit --}}
<article @class(['kcard', 'is-done' => $task->isDone(), 'is-overdue' => $task->isOverdue()])
         @if ($canEdit) draggable="true" data-task="{{ $task->id }}" data-move-url="{{ route('kegiatan.tugas.move', $task) }}" @endif>
    <div class="kcard-top">
        <span class="kcard-key">{{ $task->key() }}</span>
        <span class="prio prio-{{ $task->priority }}" title="Prioritas {{ $task->priorityLabel() }}"><x-ikon name="flag" :size="14" :fill="in_array($task->priority, ['urgent', 'high'], true)" /><span class="sr-only">Prioritas {{ $task->priorityLabel() }}</span></span>
    </div>
    <a href="{{ route('tugas.show', $task) }}" class="kcard-title">{{ $task->title }}</a>
    @if ($task->project || $task->goal || $task->labels)
        <div class="kcard-tags">
            @if ($task->project)
                <span class="tag tag-{{ $task->project->color }}" title="Proyek {{ $task->project->name }}">{{ $task->project->name }}</span>
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
        @if (($task->checklist_count ?? 0) > 0)
            <span class="meta" title="Checklist"><x-ikon name="check" :size="14" /> {{ $task->checklist_done_count }}/{{ $task->checklist_count }}</span>
        @endif
        @if (($task->evidences_count ?? 0) > 0)
            <span class="meta" title="Bukti"><x-ikon name="paperclip" :size="14" /> {{ $task->evidences_count }}</span>
        @endif
        @if (($task->comments_count ?? 0) > 0)
            <span class="meta" title="Komentar"><x-ikon name="chat" :size="14" /> {{ $task->comments_count }}</span>
        @endif
    </div>
</article>
