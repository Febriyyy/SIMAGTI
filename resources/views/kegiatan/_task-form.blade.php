{{-- Isian task (buat / ubah). Variabel: $task (null = baru), $columns, $projects, $syllabusItems --}}
@php($t = $task)
<div class="form-grid">
    <div class="span-all">
        <label class="label" for="tf-title-{{ $t?->id ?? 'new' }}">Judul task</label>
        <input id="tf-title-{{ $t?->id ?? 'new' }}" type="text" name="title" class="input" maxlength="200" required value="{{ old('title', $t?->title) }}" placeholder="Misalnya Rancang ERD modul pemesanan">
    </div>
    <div>
        <label class="label" for="tf-project-{{ $t?->id ?? 'new' }}">Proyek</label>
        <select id="tf-project-{{ $t?->id ?? 'new' }}" name="project_id" class="select">
            <option value="">Tanpa proyek</option>
            @foreach ($projects->whereNull('archived_at') as $project)
                <option value="{{ $project->id }}" @selected((int) old('project_id', $t?->project_id) === $project->id)>{{ $project->key }} · {{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="tf-col-{{ $t?->id ?? 'new' }}">Status (kolom)</label>
        <select id="tf-col-{{ $t?->id ?? 'new' }}" name="board_column_id" class="select">
            @foreach ($columns as $column)
                <option value="{{ $column->id }}" @selected((int) old('board_column_id', $t?->board_column_id) === $column->id)>{{ $column->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="tf-prio-{{ $t?->id ?? 'new' }}">Prioritas</label>
        <select id="tf-prio-{{ $t?->id ?? 'new' }}" name="priority" class="select">
            @foreach (\App\Models\WeeklyTask::PRIORITIES as $key => [$label])
                <option value="{{ $key }}" @selected(old('priority', $t?->priority ?? 'medium') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="tf-syl-{{ $t?->id ?? 'new' }}">Tujuan silabus</label>
        <select id="tf-syl-{{ $t?->id ?? 'new' }}" name="syllabus_item_id" class="select">
            <option value="">Tidak dikaitkan</option>
            @foreach ($syllabusItems->groupBy('category') as $category => $items)
                <optgroup label="{{ \App\Models\SyllabusItem::CATEGORIES[$category][0] ?? $category }}">
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected((int) old('syllabus_item_id', $t?->syllabus_item_id) === $item->id)>{{ \Illuminate\Support\Str::limit($item->title, 60) }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="tf-start-{{ $t?->id ?? 'new' }}">Mulai</label>
        <input id="tf-start-{{ $t?->id ?? 'new' }}" type="date" name="start_date" class="input" value="{{ old('start_date', $t?->start_date?->toDateString()) }}">
    </div>
    <div>
        <label class="label" for="tf-due-{{ $t?->id ?? 'new' }}">Tenggat</label>
        <input id="tf-due-{{ $t?->id ?? 'new' }}" type="date" name="due_date" class="input" value="{{ old('due_date', $t?->due_date?->toDateString()) }}">
    </div>
    <div>
        <label class="label" for="tf-est-{{ $t?->id ?? 'new' }}">Estimasi (jam)</label>
        <input id="tf-est-{{ $t?->id ?? 'new' }}" type="number" name="estimate_hours" class="input" min="0" max="500" step="0.5" value="{{ old('estimate_hours', $t?->estimate_hours !== null ? (float) $t->estimate_hours : '') }}">
    </div>
    <div>
        <label class="label" for="tf-labels-{{ $t?->id ?? 'new' }}">Label</label>
        <input id="tf-labels-{{ $t?->id ?? 'new' }}" type="text" name="labels" class="input" maxlength="120" value="{{ old('labels', implode(', ', (array) $t?->labels)) }}" placeholder="bug, frontend">
    </div>
    <div class="span-all">
        <label class="label" for="tf-desc-{{ $t?->id ?? 'new' }}">Deskripsi</label>
        <textarea id="tf-desc-{{ $t?->id ?? 'new' }}" name="description" class="textarea" rows="4" maxlength="5000" placeholder="Apa yang dikerjakan, kriteria selesai, catatan dari perusahaan…">{{ old('description', $t?->description) }}</textarea>
    </div>
</div>
