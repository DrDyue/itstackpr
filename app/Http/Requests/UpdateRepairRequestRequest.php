<?php

namespace App\Http\Requests;

use App\Models\RepairRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var RepairRequest $repairRequest */
        $repairRequest = $this->route('repairRequest');
        
        return $this->user()->id === $repairRequest->responsible_user_id 
            && $repairRequest->status === RepairRequest::STATUS_SUBMITTED;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

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
