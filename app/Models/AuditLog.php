<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    public const UPDATED_AT = null;
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
