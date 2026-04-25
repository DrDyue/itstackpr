<?php

namespace App\Http\Requests;

use App\Models\DeviceTransfer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ierīces nodošanas pieteikuma atjaunošanas validācijas noteikumi.
 *
 * Pārbauda autorizāciju — tikai iesniedzējs var rediģēt gaidošu pieteikumu.
 * Pēc tam validē jaunus datus: ierīci, saņēmēju un nodošanas iemeslu.
 */
class UpdateDeviceTransferRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieteikumu var rediģēt tikai tā iesniedzējs un tikai gaidošajā stāvoklī.
     */
    public function authorize(): bool
    {
        /** @var DeviceTransfer $deviceTransfer */
        $deviceTransfer = $this->route('deviceTransfer');
        
        return $this->user()->id === $deviceTransfer->responsible_user_id 
            && $deviceTransfer->status === DeviceTransfer::STATUS_SUBMITTED;
    }

    /**
     * Definē validācijas noteikumus rediģēšanas pieteikuma datu validācijai.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'transfered_to_id' => ['required', 'exists:users,id', 'different:user_id'],
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
            'transfered_to_id.different' => 'Nevar nodot ierīci sev pašam.',
            'transfer_reason.required' => 'Iemesls ir obligāts.',
            'transfer_reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
            'transfer_reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
        ];
    }
}
