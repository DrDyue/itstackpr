<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Remonta pieteikuma validācijas noteikumi.
 *
 * Pārbauda ierīces ID un remonta problēmas aprakstu (minimums 10, maksimums 2000 rakstzīmes).
 * Pieejams tikai autentificētiem lietotājiem.
 */
class StoreRepairRequestRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieteikumus var iesniegt tikai autentificēti lietotāji.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Definē validācijas noteikumus remonta pieteikuma datu validācijai.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
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
            'description.required' => 'Apraksts ir obligāts.',
            'description.min' => 'Aprakstam jābūt vismaz 10 rakstzīmēm.',
            'description.max' => 'Apraksts nedrīkst pārsniegt 2000 rakstzīmes.',
        ];
    }
}
