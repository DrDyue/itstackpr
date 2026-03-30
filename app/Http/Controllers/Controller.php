<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Projekta bāzes kontrolieris.
 *
 * Šeit glabājas kopīgie palīgmehānismi validācijai, lomu pārbaudēm,
 * tukšajiem paginatoriem un statusu etiķetēm.
 */
abstract class Controller
{
    /**
     * Atgriež pašreiz autorizēto lietotāju kā projekta User modeli.
     */
    protected function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Pārbauda, kā darbību veic administrators.
     */
    protected function requireAdmin(): User
    {
        $user = $this->user();

        abort_unless($user?->isAdmin(), 403);

        return $user;
    }

    /**
     * Pārbauda, kā lietotājs drīkst pārvaldīt inventāru admina skatā.
     */
    protected function requireManager(): User
    {
        $user = $this->user();

        abort_unless($user?->canManageRequests(), 403);

        return $user;
    }

    /**
     * Centralizēti pārbauda, vai konkrētā tabula datubāzē vispār eksistē.
     */
    protected function featureTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * Izveido tukšu paginatoru skatījumiem, kuros funkcija nav pieejama vai tabulas nav.
     */
    protected function emptyPaginator(int $perPage = 20): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            Paginator::resolveCurrentPage('page'),
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Vienotā validācijas ieeja ar lokalizētiem paziņojumiem un atribūtu nosaukumiem.
     */
    protected function validateInput(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return Validator::make(
            $request->all(),
            $rules,
            array_merge($this->validationMessages(), $messages),
            array_merge($this->validationAttributes(), $attributes)
        )->validate();
    }

    /**
     * Projekta kopējie validācijas tekstu šabloni.
     */
    protected function validationMessages(): array
    {
        return [
            'required' => 'Lauks ":attribute" ir obligāts. Aizpildi to un mēģini vēlreiz.',
            'string' => 'Laukam ":attribute" jābūt tekstam. Pārbaudi, vai ievadītā vērtība nav skaitlis vai tukšs saraksts.',
            'email' => 'Laukam ":attribute" jābūt derīgai e-pasta adresei. Pārbaudi, vai adrese satur @ un derīgu domēnu.',
            'unique' => 'Šāda ":attribute" vērtība jau tiek izmantota. Ievadi citu vērtību vai atver esošo ierakstu.',
            'exists' => 'Izvēlētā ":attribute" vērtība vairs nav atrasta. Izvēlies ierakstu no saraksta vēlreiz.',
            'confirmed' => 'Lauks ":attribute" nesakrīt ar apstiprinājumu. Pārbaudi abas ievadītās vērtības.',
            'date' => 'Laukam ":attribute" jābūt derīgam datumam. Izvēlies datumu kalendāra laukā vai ievadi to pareizajā formātā.',
            'numeric' => 'Laukam ":attribute" jābūt skaitlim. Izmanto tikai ciparus un decimālatdalītāju, ja tas nepieciešams.',
            'integer' => 'Laukam ":attribute" jābūt veselam skaitlim. Decimālas vērtības šeit neder.',
            'boolean' => 'Lauks ":attribute" nav derīgs. Izvēlies vienu no piedāvātajiem variantiem.',
            'array' => 'Laukam ":attribute" jābūt sarakstam. Izvēlies vienu vai vairākus ierakstus no piedāvātā saraksta.',
            'image' => 'Lauks ":attribute" drīkst satur tikai attelu failu. Izvēlies JPG, PNG vai citu atbalstītu attēla formatu.',
            'in' => 'Laukam ":attribute" ir nederīga vērtība. Izvēlies vienu no pieejamajām vērtībam.',
            'max.string' => 'Lauks ":attribute" nedrīkst būt garaks par :max simboliem. Saīsini tekstu un mēģini vēlreiz.',
            'max.numeric' => 'Lauka ":attribute" vērtība nedrīkst pārsniegt :max. Samazini ievadīto vērtību.',
            'max.file' => 'Fails ":attribute" ir par lielu. Izvēlies mazakas izmeras failu.',
            'min.string' => 'Lauks ":attribute" nedrīkst būt īsāks par :min simboliem. Papildini informāciju un mēģini vēlreiz.',
            'min.numeric' => 'Lauka ":attribute" vērtībai jābūt vismaz :min. Palielini ievadīto vērtību.',
            'min.array' => 'Izvēlies vismaz :min ":attribute" vienumu. Pievieno vēl trukstosos ierakstus.',
        ];
    }

    /**
     * Cilvēkam saprotami lauku nosaukumi validācijas kļūdām.
     */
    protected function validationAttributes(): array
    {
        return [
            'action' => 'darbība',
            'address' => 'adrese',
            'assigned_to_id' => 'piešķirtais lietotājs',
            'building_id' => 'ēka',
            'building_name' => 'ēkas nosaukums',
            'city' => 'pilsēta',
            'code' => 'kods',
            'cost' => 'izmaksas',
            'department' => 'nodala',
            'description' => 'apraksts',
            'device_id' => 'ierīce',
            'device_ids' => 'ierīces',
            'device_ids.*' => 'ierīce',
            'device_image' => 'ierīces attels',
            'device_type_id' => 'ierīces tips',
            'email' => 'e-pasts',
            'end_date' => 'beigu datums',
            'floor_number' => 'stavs',
            'full_name' => 'pilnais vards',
            'invoice_number' => 'rekina numurs',
            'is_active' => 'aktivitates statuss',
            'issue_reported_by' => 'izpildītājs',
            'job_title' => 'amats',
            'manufacturer' => 'ražotājs',
            'model' => 'modelis',
            'name' => 'nosaukums',
            'notes' => 'piezīmes',
            'password' => 'parole',
            'password_confirmation' => 'paroles apstiprinajums',
            'phone' => 'talrunis',
            'priority' => 'prioritate',
            'purchase_date' => 'iegades datums',
            'purchase_price' => 'iegades cena',
            'reason' => 'iemesls',
            'repair_type' => 'remonta tips',
            'request_id' => 'šaistitais pieteikums',
            'review_notes' => 'izskatīšanas piezīmes',
            'role' => 'loma',
            'room_id' => 'telpa',
            'room_name' => 'telpas nosaukums',
            'room_number' => 'telpas numurs',
            'serial_number' => 'sērijas numurs',
            'start_date' => 'sakuma datums',
            'status' => 'statuss',
            'target_room_id' => 'mērķa telpa',
            'target_status' => 'mērķa statuss',
            'total_floors' => 'stāvu skaits',
            'transfer_reason' => 'pārsūtīšanas iemesls',
            'transfered_to_id' => 'saņēmējs',
            'user_id' => 'atbildīgais lietotājs',
            'vendor_contact' => 'pakalpojuma sniedzēja kontakts',
            'vendor_name' => 'pakalpojuma sniedzējs',
            'warranty_until' => 'garantija līdz',
        ];
    }

    /**
     * Vienoti pieprasījumu statusu nosaukumi Blade skatījumiem un filtriem.
     */
    protected function requestStatusLabels(): array
    {
        return [
            'submitted' => 'Iesniegts',
            'approved' => 'Apstiprināts',
            'rejected' => 'Noraidits',
        ];
    }

    /**
     * Izveido remonta ierakstu, pirms tam izlīdzinot datumus legacy shēmām.
     */
    protected function createRepairRecord(array $payload): Repair
    {
        return Repair::create($this->normalizeRepairPayloadForPersistence($payload));
    }

    /**
     * Remonta payload pielāgo kolonnām, kuras dažās vidēs var nepieļaut NULL datumus.
     */
    protected function normalizeRepairPayloadForPersistence(array $payload): array
    {
        $status = (string) ($payload['status'] ?? 'waiting');
        $today = now()->toDateString();

        if (($payload['start_date'] ?? null) === null && ! $this->repairColumnAllowsNull('start_date')) {
            $payload['start_date'] = $status === 'completed'
                ? (string) ($payload['end_date'] ?? $today)
                : $today;
        }

        if (($payload['end_date'] ?? null) === null && ! $this->repairColumnAllowsNull('end_date')) {
            $payload['end_date'] = $status === 'completed'
                ? (string) ($payload['start_date'] ?? $today)
                : (string) ($payload['start_date'] ?? $today);
        }

        return $payload;
    }

    /**
     * Nolasa, vai remonta tabulas konkrētā datuma kolonna atļauj NULL vērtības.
     */
    protected function repairColumnAllowsNull(string $column): bool
    {
        static $repairsColumnNullability = null;

        if ($repairsColumnNullability === null) {
            $repairsColumnNullability = collect(Schema::getColumns('repairs'))
                ->mapWithKeys(fn (array $definition) => [
                    (string) ($definition['name'] ?? '') => (bool) ($definition['nullable'] ?? false),
                ])
                ->all();
        }

        return (bool) ($repairsColumnNullability[$column] ?? true);
    }
}
