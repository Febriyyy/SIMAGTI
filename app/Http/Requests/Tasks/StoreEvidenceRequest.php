<?php

namespace App\Http\Requests\Tasks;

use App\Models\TaskEvidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/** Bukti pengerjaan: link, berkas, atau keduanya sekaligus (minimal salah satu). */
class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('submitEvidence', $this->route('task'))->allowed();
    }

    public function rules(): array
    {
        return [
            'evidence_url' => ['nullable', 'required_without:berkas', 'url:http,https', 'max:500'],
            'title' => ['nullable', 'string', 'max:150'],
            'berkas' => ['nullable', 'array', 'max:'.TaskEvidence::MAX_FILES],
            'berkas.*' => [
                'file',
                'max:'.TaskEvidence::MAX_KB,
                'extensions:'.implode(',', array_keys(TaskEvidence::FILE_TYPES)),
                'mimetypes:'.implode(',', TaskEvidence::MIME_TYPES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence_url.required_without' => 'Isi link bukti atau pilih berkas untuk diunggah.',
            'evidence_url.url' => 'Link harus diawali http:// atau https://.',
            'berkas.max' => 'Maksimal '.TaskEvidence::MAX_FILES.' berkas sekali kirim.',
            'berkas.*.max' => 'Ukuran tiap berkas maksimal '.(TaskEvidence::MAX_KB / 1024).' MB.',
            'berkas.*.extensions' => 'Jenis berkas tidak didukung. Gunakan foto, PDF, Word, Excel, PowerPoint, ZIP/RAR/7Z, atau MP4.',
            'berkas.*.mimetypes' => 'Isi berkas tidak sesuai jenisnya.',
            'berkas.*.file' => 'Berkas gagal diunggah. Coba lagi.',
        ];
    }

    /** @return list<UploadedFile> */
    public function evidenceFiles(): array
    {
        return array_values(array_filter((array) $this->file('berkas', [])));
    }
}
