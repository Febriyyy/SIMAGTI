<?php

namespace App\Http\Requests\Activities;

use App\Models\Internship;
use App\Models\WeeklyTask;
use App\Support\ActiveInternship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Buat atau ubah task kegiatan. Task baru wajib berada di bawah satu rencana kegiatan (timesheet).
 * Semua rujukan (rencana, goal, proyek, kolom) harus milik penempatan yang sama.
 */
class SaveTaskRequest extends FormRequest
{
    private ?Internship $internship = null;

    public function internship(): Internship
    {
        if ($this->internship === null) {
            $task = $this->route('task');
            $this->internship = $task instanceof WeeklyTask ? $task->internship : ActiveInternship::for($this->user());
            abort_unless($this->internship, 404);
        }

        return $this->internship;
    }

    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof WeeklyTask
            ? Gate::forUser($this->user())->authorize('update', $task)->allowed()
            : Gate::forUser($this->user())->authorize('manageActivities', $this->internship())->allowed();
    }

    public function rules(): array
    {
        $id = $this->internship()->id;
        $creating = ! $this->route('task');
        $own = fn (string $table) => Rule::exists($table, 'id')->where('internship_id', $id);

        return [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'project_id' => ['sometimes', 'nullable', 'integer', $own('projects')],
            'board_column_id' => ['sometimes', 'nullable', 'integer', $own('board_columns')],
            'activity_plan_id' => [$creating ? 'required' : 'sometimes', 'integer', $own('activity_plans')],
            'goal_id' => ['sometimes', 'nullable', 'integer', $own('goals')],
            'priority' => ['sometimes', Rule::in(array_keys(WeeklyTask::PRIORITIES))],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date', Rule::when($this->filled('start_date'), 'after_or_equal:start_date')],
            'labels' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    /** Data siap simpan; label "bug, ui" → ["bug", "ui"] (maks. 5, masing-masing 20 huruf). */
    public function taskData(): array
    {
        $data = $this->validated();

        if (array_key_exists('labels', $data)) {
            $labels = collect(explode(',', (string) $data['labels']))
                ->map(fn ($l) => mb_substr(trim($l), 0, 20))
                ->filter()
                ->unique()
                ->take(5)
                ->values()
                ->all();
            $data['labels'] = $labels ?: null;
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'activity_plan_id.required' => 'Pilih rencana kegiatan. Isi rencana di Timesheet dulu bila belum ada.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul task',
            'project_id' => 'proyek',
            'board_column_id' => 'kolom',
            'activity_plan_id' => 'rencana kegiatan',
            'goal_id' => 'goal',
            'priority' => 'prioritas',
            'start_date' => 'tanggal mulai',
            'due_date' => 'tenggat',
        ];
    }
}
