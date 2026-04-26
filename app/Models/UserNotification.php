<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistēts paziņojums konkrētam lietotājam.
 *
 * Šo modeli izmanto jaunā paziņojumu funkcija, lai lietotājs redzētu
 * svarīgus sistēmas notikumus arī pēc lapas pārlādes. Atšķirībā no vecajiem
 * "dzīvajiem" paziņojumiem, kas tika aprēķināti no gaidošiem pieteikumiem,
 * šeit ieraksts paliek datubāzē līdz brīdim, kad lietotājs to atzīmē kā lasītu.
 */
class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'accent',
        'title',
        'message',
        'url',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Lietotājs, kuram šis paziņojums ir paredzēts.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
