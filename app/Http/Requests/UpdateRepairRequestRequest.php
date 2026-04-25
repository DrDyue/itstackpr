<?php

namespace App\Http\Requests;

use App\Models\RepairRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Remonta pieteikuma atjaunošanas validācijas noteikumi.
 *
 * Pārbauda autorizāciju — tikai iesniedzējs var rediģēt gaidošu pieteikumu.
 * Pēc tam validē jaunus datus: ierīci un remonta problēmas aprakstu.
 */
class UpdateRepairRequestRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieteikumu var rediģēt tikai tā iesniedzējs un tikai gaidošajā stāvoklī.
     */
    public function authorize(): bool
    {
        /** @var RepairRequest $repairRequest */
        $repairRequest = $this->route('repairRequest');
        
        return $this->user()->id === $repairRequest->responsible_user_id 
            && $repairRequest->status === RepairRequest::STATUS_SUBMITTED;
    }

    /**
     * Definē validācijas noteikumus rediģēšanas pieteikuma datu validācijai.
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
