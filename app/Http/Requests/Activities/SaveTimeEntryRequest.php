<?php

namespace App\Http\Requests\Activities;

use App\Models\Internship;
use App\Models\TimeEntry;
use App\Support\ActiveInternship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Satu baris timesheet: tanggal dalam periode KP (tidak di masa depan), durasi jam, kegiatan, foto opsional. */
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
            'hours' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0.25', 'max:'.TimeEntry::MAX_HOURS],
            'description' => [$partial ? 'sometimes' : 'required', 'string', 'max:1000'],
            'weekly_task_id' => ['sometimes', 'nullable', 'integer', Rule::exists('weekly_tasks', 'id')->where('internship_id', $internship->id)->whereNull('deleted_at')],
            'foto' => ['nullable', 'file', 'max:5120', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp'],
        ];
    }

    /** Total jam satu hari tidak boleh lebih dari 24 jam. */
    public function after(): array
    {
        return [function (\Illuminate\Validation\Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $entry = $this->route('entry');
            $date = $this->input('work_date', $entry instanceof TimeEntry ? $entry->work_date->toDateString() : null);
            $hours = (float) $this->input('hours', $entry instanceof TimeEntry ? $entry->hours : 0);

            $other = (float) $this->internship()->timeEntries()
                ->whereDate('work_date', $date)
                ->when($entry instanceof TimeEntry, fn ($q) => $q->whereKeyNot($entry->id))
                ->sum('hours');

            if ($other + $hours > 24) {
                $validator->errors()->add('hours', 'Total jam pada tanggal ini melebihi 24 jam (sudah tercatat '.rtrim(rtrim(number_format($other, 2, ',', ''), '0'), ',').' jam).');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'work_date.after_or_equal' => 'Tanggal harus di dalam periode KP.',
            'work_date.before_or_equal' => 'Tanggal tidak boleh di masa depan atau setelah KP selesai.',
            'hours.max' => 'Satu catatan maksimal '.TimeEntry::MAX_HOURS.' jam.',
            'foto.extensions' => 'Foto harus JPG, PNG, atau WEBP.',
            'foto.mimetypes' => 'Isi berkas bukan foto.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return ['work_date' => 'tanggal', 'hours' => 'durasi', 'description' => 'kegiatan', 'weekly_task_id' => 'task'];
    }
}
