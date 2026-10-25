<?php

namespace App\Http\Requests\Meetings;

use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreMeetingRequest extends FormRequest
{
    private ?Collection $internships = null;

    /** @return list<int> */
    private function ids(): array
    {
        $raw = $this->input('magang');

        return is_array($raw)
            ? array_values(array_unique(array_map('intval', $raw)))
            : [];
    }

    /** Penempatan yang dipilih mentor (setelah lolos pengecekan akses). */
    public function internships(): Collection
    {
        return $this->internships ??= Internship::with('student')->whereIn('id', $this->ids())->get();
    }

    /**
     * Semua mahasiswa yang dipilih harus bimbingan mentor ini.
     * Jika tidak ada yang dipilih, biarkan validasi yang memberi pesan.
     */
    public function authorize(): bool
    {
        $ids = $this->ids();

        if ($ids === []) {
            return true;
        }

        $internships = $this->internships();

        if ($internships->count() !== count($ids)) {
            return false;
        }

        foreach ($internships as $internship) {
            Gate::forUser($this->user())->authorize('scheduleMeeting', $internship);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'magang' => ['required', 'array', 'min:1'],
            'magang.*' => ['integer'],
            'day' => ['required', Rule::in(array_keys(WeeklyTask::DAYS))],
            'time' => ['required', 'date_format:H:i'],
            'url' => ['required', 'url:http,https', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'magang.required' => 'Pilih minimal satu mahasiswa.',
            'magang.min' => 'Pilih minimal satu mahasiswa.',
            'time.date_format' => 'Jam harus berformat 24 jam, misalnya 10:00.',
            'url.required' => 'Link meeting wajib diisi.',
            'url.url' => 'Link meeting harus diawali http:// atau https://.',
        ];
    }
}
