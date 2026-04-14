<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWriteoffRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Jāizvēlas ierīce.',
            'device_id.exists' => 'Izvēlētā ierīce neeksistē.',
            'reason.required' => 'Iemesls ir obligāts.',
            'reason.min' => 'Iemesls jābūt vismaz 10 rakstzīmēm.',
            'reason.max' => 'Iemesls ne vairāk kā 2000 rakstzīmes.',
        ];
    }
}
