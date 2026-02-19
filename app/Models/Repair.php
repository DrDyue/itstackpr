<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    protected $table = 'repairs';

    // В таблице есть только created_at (нет updated_at)
    const UPDATED_AT = null;

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

    public function reporter()
    {
        return $this->belongsTo(User::class, 'issue_reported_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
