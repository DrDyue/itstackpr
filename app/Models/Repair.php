<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    protected $table = 'repairs';

    public const UPDATED_AT = null; // у нас нет updated_at
    public $timestamps = true; // created_at есть

    protected $fillable = [
        'device_id',
        'description',
        'status',
        'repair_type',
        'priority',
        'start_date',
        'estimated_completion',
        'actual_completion',
        'cost',
        'vendor_name',
        'vendor_contact',
        'invoice_number',
        'issue_reported_by',
        'assigned_to',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
