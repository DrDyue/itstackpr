<?php

namespace App\Http\Controllers\Traits;

/**
 * Pārtvertā loģika remonta statusa tulkošanai uz cilvēkam saprotamiem zīmēm.
 *
 * Šis traits glabā centralizēti visus dažādu remonta statusiun pieprasījumu
 * statusus, lai nodrošinātu konsekventi labeles visā sistēmā.
 */
trait HasRepairStatusLabels
{
    /**
     * Konvertē remonta statusu uz latviskiem statusu labeliem.
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
