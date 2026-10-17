<?php

namespace App\Http\Requests\Tasks;

use App\Models\TaskAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** Mentor menambah lampiran pada task bimbingannya (selama KP belum dikunci). */
class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('update', $this->route('task'))->allowed();
    }

    public function rules(): array
    {
        $rules = TaskAttachment::rules();
        $rules['lampiran'] = ['required', ...array_diff($rules['lampiran'], ['nullable'])];

        return $rules;
    }

    public function messages(): array
    {
        return ['lampiran.required' => 'Pilih minimal satu berkas.'] + TaskAttachment::messages();
    }

    /** @return list<\Illuminate\Http\UploadedFile> */
    public function attachments(): array
    {
        return array_values($this->file('lampiran', []));
    }
}
