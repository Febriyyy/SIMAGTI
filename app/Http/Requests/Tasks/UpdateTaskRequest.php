<?php

namespace App\Http\Requests\Tasks;

use App\Models\WeeklyTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('update', $this->route('task'))->allowed();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(WeeklyTask::STATUSES))],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
