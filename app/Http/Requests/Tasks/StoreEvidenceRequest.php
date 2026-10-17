<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('submitEvidence', $this->route('task'))->allowed();
    }

    public function rules(): array
    {
        return [
            'evidence_url' => ['required', 'url:http,https', 'max:500'],
            'title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
