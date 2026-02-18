<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'job_title',
        'is_active'
    ];
}
