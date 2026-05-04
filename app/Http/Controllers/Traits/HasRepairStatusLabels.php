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
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
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
