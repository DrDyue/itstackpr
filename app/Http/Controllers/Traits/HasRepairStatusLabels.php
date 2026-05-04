<?php

namespace App\Http\Controllers\Traits;

/**
 * Ko dara: Nodrošina kopīgu remonta statusu etiķešu loģiku.
 *
 * Kā strādā: Pārvērš tehniskos remonta statusus cilvēkam saprotamos tekstos, ko var izmantot vairākos kontrolieros.
 *
 * Kad pielietojas: Kad kontrolierim jāparāda remonta statuss lietotājam saprotamā veidā.
 */
trait HasRepairStatusLabels
{
    /**
     * Ko dara: Konvertē remonta statusu uz latviskiem statusu labeliem.
     *
     * Kā strādā: Ar `match` izteiksmi tehnisko statusa vērtību (`waiting`, `in-progress`, `completed`, `cancelled`) pārvērš īsā latviskā nosaukumā.
     *
     * Kad pielietojas: Kad kontrolierim paziņojumā, kartītē vai sarakstā jāparāda remonta statuss lietotājam saprotamā tekstā.
     */
    private function repairStatusLabel(?string $status): string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            default => 'Remonta',
        };
    }
}
