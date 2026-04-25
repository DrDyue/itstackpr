<?php

namespace App\Http\Requests;

use App\Models\WriteoffRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Norakstīšanas pieteikuma atjaunošanas validācijas noteikumi.
 *
 * Pārbauda autorizāciju — tikai iesniedzējs var rediģēt gaidošu pieteikumu.
 * Pēc tam validē jaunus datus: ierīci un norakstīšanas iemeslu.
 */
class UpdateWriteoffRequestRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieteikumu var rediģēt tikai tā iesniedzējs un tikai gaidošajā stāvoklī.
     */
    public function authorize(): bool
    {
        /** @var WriteoffRequest $writeoffRequest */
        $writeoffRequest = $this->route('writeoffRequest');
        
        return $this->user()->id === $writeoffRequest->responsible_user_id 
            && $writeoffRequest->status === WriteoffRequest::STATUS_SUBMITTED;
    }

    /**
     * Definē validācijas noteikumus rediģēšanas pieteikuma datu validācijai.
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
