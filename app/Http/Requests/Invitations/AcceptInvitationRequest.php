<?php

namespace App\Http\Requests\Invitations;

use Illuminate\Foundation\Http\FormRequest;

class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // halaman publik; keabsahan dicek lewat token di Action
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'password' => 'kata sandi'];
    }
}
