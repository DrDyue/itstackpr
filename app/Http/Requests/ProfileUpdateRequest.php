<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profila atjaunošanas validācijas noteikumi.
 *
 * Pārbauda autentificēta lietotāja profila pamatdatus: vārds, e-pasts, tālrunis un amats.
 * E-pasta unikālumā ignorē pašreizējo lietotāju.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Definē validācijas noteikumus profila datu validācijai.
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'phone' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ];
    }
}
