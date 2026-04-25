<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Norakstīšanas pieteikuma validācijas noteikumi.
 *
 * Pārbauda ierīces ID un norakstīšanas iemeslu (minimums 10, maksimums 2000 rakstzīmes).
 * Pieejams tikai autentificētiem lietotājiem.
 */
class StoreWriteoffRequestRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieteikumus var iesniegt tikai autentificēti lietotāji.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Definē validācijas noteikumus norakstīšanas pieteikuma datu validācijai.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
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
            'reason.required' => 'Iemesls ir obligāts.',
            'reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
            'reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
        ];
    }
}
