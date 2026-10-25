<?php

namespace App\Http\Requests\Syllabus;

use App\Models\SyllabusItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSyllabusItemRequest extends FormRequest
{
    /** Hak akses dicek di controller (penempatan aktif milik mahasiswa + status silabus). */
    public function authorize(): bool
    {
        return $this->user()->role === 'mahasiswa';
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(SyllabusItem::CATEGORIES))],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['category' => 'kategori', 'title' => 'tujuan', 'description' => 'keterangan'];
    }
}
