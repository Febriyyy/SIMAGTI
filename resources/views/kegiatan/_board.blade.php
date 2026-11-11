{{-- Board kanban. Variabel: $columns, $grouped, $canEdit, $showGoals (kolom Goals di kiri, opsional; perlu $goals) --}}
<div @class(['board', 'has-goals' => $showGoals ?? false]) data-board>
    @if ($showGoals ?? false)
        @include('kegiatan._goals-col')
    @endif
    @foreach ($columns as $column)
        @php($items = $grouped[$column->id] ?? collect())
        <section class="kcol" data-column="{{ $column->id }}">
            <header class="kcol-head">
                <span class="kcol-dot tone-bg-{{ $column->tone() }}"></span>
                <h3 class="kcol-title">{{ $column->name }}</h3>
                <span class="kcol-count" data-count>{{ $items->count() }}</span>
            </header>
            <div class="kcol-body" data-drop>
                @foreach ($items as $task)
                    @include('kegiatan._card', ['task' => $task, 'canEdit' => $canEdit])
                @endforeach
                <p class="kcol-empty" @if ($items->isNotEmpty()) hidden @endif>{{ $canEdit ? 'Tarik task ke sini' : 'Kosong' }}</p>
            </div>
            @if ($canEdit)
                <form method="POST" action="{{ route('kegiatan.tugas.store') }}" class="kcol-add">
                    @csrf
                    <input type="hidden" name="board_column_id" value="{{ $column->id }}">
                    <input type="text" name="title" class="input input-sm" maxlength="200" required placeholder="+ Tambah task" aria-label="Task baru di {{ $column->name }}">
                </form>
            @endif
        </section>
    @endforeach
</div>
