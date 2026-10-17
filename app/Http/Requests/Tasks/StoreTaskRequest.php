<?php

namespace App\Http\Requests\Tasks;

use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    private ?Internship $internship = null;

    /** Penempatan tujuan task, diambil dari input "magang". */
    public function internship(): Internship
    {
        return $this->internship ??= Internship::with('student')->findOrFail($this->integer('magang'));
    }

    /** Hak akses dicek lebih dulu: pesan 403 muncul sebelum validasi isian. */
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('manageTasks', $this->internship())->allowed();
    }

    public function rules(): array
    {
        return [
            'minggu' => ['required', 'integer', 'min:1', 'max:'.$this->internship()->totalWeeks()],
            'day' => ['required', Rule::in(array_keys(WeeklyTask::DAYS))],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
