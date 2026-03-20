<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    protected function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected function requireAdmin(): User
    {
        $user = $this->user();

        abort_unless($user?->isAdmin(), 403);

        return $user;
    }

    protected function requireManager(): User
    {
        $user = $this->user();

        abort_unless($user?->canManageRequests(), 403);

        return $user;
    }

    protected function featureTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

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

    protected function validateInput(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return Validator::make(
            $request->all(),
            $rules,
            array_merge($this->validationMessages(), $messages),
            array_merge($this->validationAttributes(), $attributes)
        )->validate();
    }

    protected function validationMessages(): array
    {
        return [
            'required' => 'Lauks ":attribute" ir obligats.',
            'string' => 'Laukam ":attribute" jabut tekstam.',
            'email' => 'Laukam ":attribute" jabut derigai e-pasta adresei.',
            'unique' => 'Sada ":attribute" vertiba jau tiek izmantota.',
            'exists' => 'Izveleta ":attribute" vertiba nav atrasta.',
            'confirmed' => 'Lauks ":attribute" nesakrit ar apstiprinajumu.',
            'date' => 'Laukam ":attribute" jabut derigam datumam.',
            'numeric' => 'Laukam ":attribute" jabut skaitlim.',
            'integer' => 'Laukam ":attribute" jabut veselam skaitlim.',
            'boolean' => 'Lauks ":attribute" nav derigs.',
            'array' => 'Laukam ":attribute" jabut sarakstam.',
            'image' => 'Lauks ":attribute" drikst satur tikai attelu failu.',
            'in' => 'Laukam ":attribute" ir nederiga vertiba.',
            'max.string' => 'Lauks ":attribute" nedrikst but garaks par :max simboliem.',
            'max.numeric' => 'Lauka ":attribute" vertiba nedrikst parsniegt :max.',
            'max.file' => 'Fails ":attribute" ir par lielu.',
            'min.string' => 'Lauks ":attribute" nedrikst but isaks par :min simboliem.',
            'min.numeric' => 'Lauka ":attribute" vertibai jabut vismaz :min.',
            'min.array' => 'Izvelies vismaz :min ":attribute" vienumu.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'action' => 'darbiba',
            'address' => 'adrese',
            'assigned_to_id' => 'pieskirtais lietotajs',
            'building_id' => 'eka',
            'building_name' => 'ekas nosaukums',
            'city' => 'pilseta',
            'code' => 'kods',
            'cost' => 'izmaksas',
            'department' => 'nodala',
            'description' => 'apraksts',
            'device_id' => 'ierice',
            'device_ids' => 'ierices',
            'device_ids.*' => 'ierice',
            'device_image' => 'ierices attels',
            'device_type_id' => 'ierices tips',
            'email' => 'e-pasts',
            'end_date' => 'beigu datums',
            'floor_number' => 'stavs',
            'full_name' => 'pilnais vards',
            'invoice_number' => 'rekina numurs',
            'is_active' => 'aktivitates statuss',
            'issue_reported_by' => 'izpilditajs',
            'job_title' => 'amats',
            'manufacturer' => 'razotajs',
            'model' => 'modelis',
            'name' => 'nosaukums',
            'notes' => 'piezimes',
            'password' => 'parole',
            'password_confirmation' => 'paroles apstiprinajums',
            'phone' => 'talrunis',
            'priority' => 'prioritate',
            'purchase_date' => 'iegades datums',
            'purchase_price' => 'iegades cena',
            'reason' => 'iemesls',
            'repair_type' => 'remonta tips',
            'request_id' => 'saistitais pieteikums',
            'review_notes' => 'izskatisanas piezimes',
            'role' => 'loma',
            'room_id' => 'telpa',
            'room_name' => 'telpas nosaukums',
            'room_number' => 'telpas numurs',
            'serial_number' => 'serijas numurs',
            'start_date' => 'sakuma datums',
            'status' => 'statuss',
            'target_room_id' => 'merka telpa',
            'target_status' => 'merka statuss',
            'total_floors' => 'stavu skaits',
            'transfer_reason' => 'parsutisanas iemesls',
            'transfered_to_id' => 'sanemejs',
            'user_id' => 'atbildigais lietotajs',
            'vendor_contact' => 'pakalpojuma sniedzeja kontakts',
            'vendor_name' => 'pakalpojuma sniedzejs',
            'warranty_until' => 'garantija lidz',
        ];
    }

    protected function requestStatusLabels(): array
    {
        return [
            'submitted' => 'Iesniegts',
            'approved' => 'Apstiprinats',
            'rejected' => 'Noraidits',
        ];
    }

    protected function createRepairRecord(array $payload): Repair
    {
        return Repair::create($this->normalizeRepairPayloadForPersistence($payload));
    }

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
