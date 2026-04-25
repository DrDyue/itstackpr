<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ierīces nodošanas pieteikuma validācijas noteikumi.
 *
 * Pārbauda ierīces ID, saņēmēja ID un nodošanas iemeslu.
 * Saņēmējs nedrīkst būt pats pieteikuma iesniedzējs.
 * Pieejams tikai autentificētiem lietotājiem.
 */
class StoreDeviceTransferRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieteikumus var iesniegt tikai autentificēti lietotāji.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Definē validācijas noteikumus nodošanas pieteikuma datu validācijai.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'transfered_to_id' => ['required', 'exists:users,id', Rule::notIn([$this->user()->id])],
            'transfer_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * Nodrošina lietotāju draudzīgus kļūdu paziņojumus validācijas kļūmju gadījumā.
     */
    public function messages(): array
    {
        return [
            'device_id.required' => 'Jāizvēlas ierīce.',
            'device_id.exists' => 'Izvēlētā ierīce neeksistē.',
            'transfered_to_id.required' => 'Jānorāda, kam nodot ierīci.',
            'transfered_to_id.exists' => 'Izvēlētais lietotājs neeksistē.',
            'transfered_to_id.not_in' => 'Nevar nodot ierīci sev pašam.',
            'transfer_reason.required' => 'Iemesls ir obligāts.',
            'transfer_reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
            'transfer_reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
        ];
    }
}
