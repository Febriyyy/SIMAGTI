<?php

namespace App\Http\Requests\Activities;

use App\Models\Internship;
use App\Models\TimeEntry;
use App\Support\ActiveInternship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Satu catatan kegiatan: tanggal dalam periode KP (tidak di masa depan), kegiatan, task terkait, foto opsional. */
class SaveTimeEntryRequest extends FormRequest
{
    private ?Internship $internship = null;

    public function internship(): Internship
    {
        if ($this->internship === null) {
            $entry = $this->route('entry');
            $this->internship = $entry instanceof TimeEntry ? $entry->internship : ActiveInternship::for($this->user());
            abort_unless($this->internship, 404);
        }

        return $this->internship;
    }

    public function authorize(): bool
    {
        $entry = $this->route('entry');
        if ($entry instanceof TimeEntry && (int) $entry->created_by !== (int) $this->user()->id) {
            return false;
        }

        return Gate::forUser($this->user())->authorize('manageActivities', $this->internship())->allowed();
    }

    public function rules(): array
    {
        $internship = $this->internship();
        $last = min($internship->end_date->toDateString(), now()->toDateString());
        $partial = $this->isMethod('patch');

        return [
            'work_date' => [$partial ? 'sometimes' : 'required', 'date', 'after_or_equal:'.$internship->start_date->toDateString(), 'before_or_equal:'.$last],
            'description' => [$partial ? 'sometimes' : 'required', 'string', 'max:1000'],
            'weekly_task_id' => ['sometimes', 'nullable', 'integer', Rule::exists('weekly_tasks', 'id')->where('internship_id', $internship->id)->whereNull('deleted_at')],
            'foto' => ['nullable', 'file', 'max:5120', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_date.after_or_equal' => 'Tanggal harus di dalam periode KP.',
            'work_date.before_or_equal' => 'Tanggal tidak boleh di masa depan atau setelah KP selesai.',
            'foto.extensions' => 'Foto harus JPG, PNG, atau WEBP.',
            'foto.mimetypes' => 'Isi berkas bukan foto.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return ['work_date' => 'tanggal', 'description' => 'kegiatan', 'weekly_task_id' => 'task'];
    }
}
