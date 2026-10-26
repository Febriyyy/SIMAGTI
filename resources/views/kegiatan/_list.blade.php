{{-- Tampilan list. Variabel: $tasks, $sort, $filter --}}
@if ($tasks->isEmpty())
    <div class="empty">
        <strong>Tidak ada task</strong>
        {{ $filter->isActive() ? 'Tidak ada task yang cocok dengan filter.' : 'Belum ada task.' }}
    </div>
@else
    <div class="table-wrap">
        <table class="table table-compact">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th style="width: 40%">Task</th>
                    <th>Status</th>
                    <th>Prioritas</th>
                    <th>Tenggat</th>
                    <th>Rencana</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr @class(['is-muted' => $task->isDone()])>
                        <td class="kcard-key">{{ $task->key() }}</td>
                        <td class="col-fluid">
                            <a href="{{ route('tugas.show', $task) }}" class="cell-title truncate" title="{{ $task->title }}">{{ $task->title }}</a>
                            <div class="cell-sub truncate">
                                {{ $task->project?->name ?? 'Tanpa proyek' }}
                                @if (($task->evidences_count ?? 0) > 0) · {{ $task->evidences_count }} bukti @endif
                                @if (($task->comments_count ?? 0) > 0) · {{ $task->comments_count }} komentar @endif
                            </div>
                        </td>
                        <td><x-chip :tone="$task->column?->tone() ?? $task->statusTone()">{{ $task->column?->name ?? $task->statusLabel() }}</x-chip></td>
                        <td><span class="prio prio-{{ $task->priority }}"><x-ikon name="flag" :size="14" :fill="in_array($task->priority, ['urgent', 'high'], true)" /> {{ $task->priorityLabel() }}</span></td>
                        <td @class(['is-late' => $task->isOverdue()])>{{ $task->due_date?->locale('id')->translatedFormat('j M Y') ?? '–' }}</td>
                        <td><span class="truncate" title="{{ $task->plan?->title }}">{{ $task->plan ? \Illuminate\Support\Str::limit($task->plan->title, 28) : '–' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
