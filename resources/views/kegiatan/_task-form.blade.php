{{-- Isian task (buat / ubah). Variabel: $task (null = baru), $columns, $projects, $plans, $goals --}}
@php($t = $task)
<div class="form-grid">
    <div class="span-all">
        <label class="label" for="tf-plan-{{ $t?->id ?? 'new' }}">Rencana kegiatan</label>
        <select id="tf-plan-{{ $t?->id ?? 'new' }}" name="activity_plan_id" class="select" required>
            <option value="" disabled @selected(! old('activity_plan_id', $t?->activity_plan_id))>Pilih rencana dari timesheet…</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((int) old('activity_plan_id', $t?->activity_plan_id ?? request('rencana')) === $plan->id)>{{ \Illuminate\Support\Str::limit($plan->title, 60) }} · {{ $plan->rangeLabel() }}</option>
            @endforeach
        </select>
    </div>
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
        <label class="label" for="tf-goal-{{ $t?->id ?? 'new' }}">Goal</label>
        <select id="tf-goal-{{ $t?->id ?? 'new' }}" name="goal_id" class="select">
            <option value="">Tidak dikaitkan</option>
            @foreach ($goals as $goal)
                <option value="{{ $goal->id }}" @selected((int) old('goal_id', $t?->goal_id) === $goal->id)>{{ \Illuminate\Support\Str::limit($goal->title, 60) }}</option>
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
        <label class="label" for="tf-labels-{{ $t?->id ?? 'new' }}">Label</label>
        <input id="tf-labels-{{ $t?->id ?? 'new' }}" type="text" name="labels" class="input" maxlength="120" value="{{ old('labels', implode(', ', (array) $t?->labels)) }}" placeholder="bug, frontend">
    </div>
    <div class="span-all">
        <label class="label" for="tf-desc-{{ $t?->id ?? 'new' }}">Deskripsi</label>
        <textarea id="tf-desc-{{ $t?->id ?? 'new' }}" name="description" class="textarea" rows="4" maxlength="5000" placeholder="Apa yang dikerjakan, kriteria selesai, catatan dari perusahaan…">{{ old('description', $t?->description) }}</textarea>
    </div>
</div>
