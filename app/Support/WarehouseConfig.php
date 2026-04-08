<?php

namespace App\Support;

/**
 * Noliktavas konfigurācijas konstantes.
 *
 * Šī klase centralizē visas noliktavas piesaistes konfigurācijas,
 * lai nodrošinātu vienotu pieeju sistēmā.
 */
class WarehouseConfig
{
    /**
     * Noliktavas telpas nosaukums.
     */
    public const DEFAULT_ROOM_NAME = 'Noliktava';

    /**
     * Noliktavas telpas numura prefikss.
     */
    public const DEFAULT_ROOM_NUMBER_PREFIX = 'NOL-';

    /**
     * Noliktavas ēkas nosaukums.
     */
    public const DEFAULT_BUILDING_NAME = 'Ludzas novada pašvaldība';
}
