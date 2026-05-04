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
 * Ko dara: Nodrošina kopīgās palīgmetodes visiem Laravel kontrolieriem projektā.
 *
 * Kā strādā: Centralizē lietotāja iegūšanu, tiesību pārbaudes, tabulu pieejamības pārbaudi, validācijas ziņojumus un remonta ierakstu izveides palīgloģiku.
 *
 * Kad pielietojas: Kad jebkuram konkrētam kontrolierim vajag kopīgu autorizācijas, validācijas vai remonta saglabāšanas funkcionalitāti.
 */
abstract class Controller
{
    /**
     * Ko dara: Atgriež pašreiz autorizēto lietotāju kā projekta User modeli.
     *
     * Kā strādā: Paņem `auth()->user()` un pārbauda, vai tas ir projekta `User` modelis, nevis cita autentifikācijas objekta instance.
     *
     * Kad pielietojas: Kad kontrolierim vajag droši nolasīt pierakstīto lietotāju pirms tiesību vai datu redzamības pārbaudes.
     */
    protected function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Ko dara: Pārbauda, vai darbību veic administrators.
     *
     * Kā strādā: Izsauc `user()`, pārbauda `isAdmin()` un ar 403 aptur pieprasījumu, ja lietotājs nav administrators.
     *
     * Kad pielietojas: Administratoru sadaļās pirms darbībām ar lietotājiem, auditu vai sistēmas konfigurāciju.
     */
    protected function requireAdmin(): User
    {
        $user = $this->user();

        abort_unless($user?->isAdmin(), 403);

        return $user;
    }

    /**
     * Ko dara: Pārbauda, vai lietotājs drīkst pārvaldīt inventāru admina skatā.
     *
     * Kā strādā: Izsauc `user()`, pārbauda `canManageRequests()` un atgriež lietotāju tikai tad, ja viņš drīkst pārvaldīt pieteikumus un inventāru.
     *
     * Kad pielietojas: Vadītāja/admina funkcijās, kur parastam darbiniekam piekļuve nav atļauta.
     */
    protected function requireManager(): User
    {
        $user = $this->user();

        abort_unless($user?->canManageRequests(), 403);

        return $user;
    }

    /**
     * Ko dara: Centralizēti pārbauda, vai konkrētā tabula datubāzē vispār eksistē.
     *
     * Kā strādā: Izmanto Laravel Schema pārbaudi, lai pirms vaicājumiem noskaidrotu, vai konkrētā funkcijas tabula eksistē datubāzē.
     *
     * Kad pielietojas: Sadaļās, kurām jāstrādā arī legacy instalācijā ar nepilnām migrācijām.
     */
    protected function featureTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * Ko dara: Izveido tukšu paginatoru skatījumiem, kuros funkcija nav pieejama vai tabulas nav.
     *
     * Kā strādā: Izveido Laravel `LengthAwarePaginator` ar tukšu datu masīvu, nulles kopskaitu un pašreizējo lapas ceļu.
     *
     * Kad pielietojas: Kad saraksta skats jāparāda bez kļūdas, lai gan attiecīgā tabula vai funkcionalitāte nav pieejama.
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
     * Ko dara: Vienotā validācijas ieeja ar lokalizētiem paziņojumiem un atribūtu nosaukumiem.
     *
     * Kā strādā: Apvieno projekta noklusētos validācijas tekstus ar konkrētās formas tekstiem un pārbauda visu pieprasījuma ievadi pret padotajiem noteikumiem.
     *
     * Kad pielietojas: Kontrolieru `store`, `update` un citās POST/PATCH darbībās, kur jāvalidē lietotāja ievadītie dati.
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
     * Ko dara: Projekta kopējie validācijas tekstu šabloni.
     *
     * Kā strādā: Atgriež vienotu Laravel validācijas noteikumu tekstu masīvu latviešu valodā, ko izmanto visas formas.
     *
     * Kad pielietojas: Katru reizi, kad `validateInput()` veido validācijas kļūdu paziņojumus.
     */
    protected function validationMessages(): array
    {
        // Šī vārdnīca satur projekta kopīgos kļūdu tekstus, lai dažādās formās
        // lietotājs redzētu vienādu un saprotamu validācijas valodu.
        return [
            'required' => 'Aizpildi lauku ":attribute".',
            'required_if' => 'Aizpildi lauku ":attribute", jo tas ir nepieciešams izvēlētajai darbībai.',
            'required_with' => 'Aizpildi lauku ":attribute", jo ir aizpildīts saistīts lauks.',
            'string' => 'Laukā ":attribute" ievadi tekstu.',
            'email' => 'Laukā ":attribute" ievadi derīgu e-pasta adresi, piemēram, vards@domeins.lv.',
            'unique' => 'Šāda ":attribute" vērtība jau tiek izmantota. Ievadi citu vērtību vai atver esošo ierakstu.',
            'exists' => 'Izvēlētā ":attribute" vērtība vairs nav pieejama. Atsvaidzini lapu un izvēlies ierakstu vēlreiz.',
            'confirmed' => 'Lauks ":attribute" nesakrīt ar apstiprinājumu. Pārbaudi abas ievadītās vērtības.',
            'date' => 'Laukā ":attribute" ievadi derīgu datumu.',
            'numeric' => 'Laukā ":attribute" ievadi skaitli.',
            'integer' => 'Laukā ":attribute" ievadi veselu skaitli.',
            'boolean' => 'Laukam ":attribute" ir nederīga izvēle. Izvēlies vienu no piedāvātajiem variantiem.',
            'array' => 'Laukam ":attribute" jābūt sarakstam. Izvēlies vienu vai vairākus ierakstus.',
            'image' => 'Laukā ":attribute" augšupielādē attēla failu, piemēram, JPG vai PNG.',
            'in' => 'Laukam ":attribute" ir nederīga vērtība. Izvēlies vienu no pieejamajām vērtībām.',
            'different' => 'Laukam ":attribute" jāatšķiras no lauka ":other".',
            'not_in' => 'Izvēlētā ":attribute" vērtība nav atļauta šai darbībai.',
            'current_password' => 'Ievadītā parole nav pareiza.',
            'after_or_equal' => 'Laukam ":attribute" jābūt pēc vai tajā pašā dienā kā ":date".',
            'before_or_equal' => 'Laukam ":attribute" jābūt pirms vai tajā pašā dienā kā ":date".',
            'max.string' => 'Lauks ":attribute" nedrīkst būt garāks par :max rakstzīmēm.',
            'max.numeric' => 'Lauka ":attribute" vērtība nedrīkst pārsniegt :max.',
            'max.file' => 'Fails ":attribute" ir par lielu. Augšupielādē mazāku failu.',
            'min.string' => 'Lauks ":attribute" nedrīkst būt īsāks par :min rakstzīmēm.',
            'min.numeric' => 'Lauka ":attribute" vērtībai jābūt vismaz :min.',
            'min.array' => 'Izvēlies vismaz :min ierakstu laukā ":attribute".',
        ];

    }

    /**
     * Ko dara: Cilvēkam saprotami lauku nosaukumi validācijas kļūdām.
     *
     * Kā strādā: Atgriež masīvu, kur katra formas lauka tehniskais nosaukums sasaistīts ar latvisku nosaukumu validācijas kļūdām.
     *
     * Kad pielietojas: Kad validācijas kļūdā jāparāda saprotams lauka nosaukums, piemēram, "ierīce", nevis `device_id`.
     */
    protected function validationAttributes(): array
    {
        // Šī vārdnīca pārvērš tehniskos formu lauku nosaukumus saprotamā latviešu
        // tekstā visiem kontrolieriem, kas izmanto kopīgo validateInput metodi.
        return [
            'action' => 'darbība',
            'address' => 'adrese',
            'assigned_to_id' => 'piešķirtais lietotājs',
            'building_id' => 'ēka',
            'building_name' => 'ēkas nosaukums',
            'city' => 'pilsēta',
            'code' => 'kods',
            'cost' => 'izmaksas',
            'current_password' => 'pašreizējā parole',
            'department' => 'nodaļa',
            'description' => 'apraksts',
            'device_id' => 'ierīce',
            'device_ids' => 'ierīces',
            'device_ids.*' => 'ierīce',
            'device_image' => 'ierīces attēls',
            'device_type_id' => 'ierīces tips',
            'email' => 'e-pasts',
            'end_date' => 'beigu datums',
            'floor_number' => 'stāvs',
            'full_name' => 'vārds un uzvārds',
            'hide_written_off_devices' => 'norakstīto ierīču slēpšana',
            'invoice_number' => 'rēķina numurs',
            'is_active' => 'aktivitātes statuss',
            'issue_reported_by' => 'izpildītājs',
            'job_title' => 'amats',
            'manufacturer' => 'ražotājs',
            'model' => 'modelis',
            'name' => 'nosaukums',
            'notes' => 'piezīmes',
            'password' => 'parole',
            'password_confirmation' => 'paroles apstiprinājums',
            'phone' => 'tālrunis',
            'priority' => 'prioritāte',
            'purchase_date' => 'iegādes datums',
            'purchase_price' => 'iegādes cena',
            'reason' => 'iemesls',
            'repair_type' => 'remonta tips',
            'request_id' => 'saistītais pieteikums',
            'request_type' => 'pieteikuma tips',
            'review_notes' => 'izskatīšanas piezīmes',
            'role' => 'loma',
            'room_id' => 'telpa',
            'room_name' => 'telpas nosaukums',
            'room_number' => 'telpas numurs',
            'serial_number' => 'sērijas numurs',
            'start_date' => 'sākuma datums',
            'status' => 'statuss',
            'target_assigned_to_id' => 'mērķa atbildīgais',
            'target_room_id' => 'mērķa telpa',
            'target_status' => 'mērķa statuss',
            'total_floors' => 'stāvu skaits',
            'transfer_reason' => 'nodošanas iemesls',
            'transfered_to_id' => 'saņēmējs',
            'user_id' => 'atbildīgais lietotājs',
            'vendor_contact' => 'pakalpojuma sniedzēja kontakts',
            'vendor_name' => 'pakalpojuma sniedzējs',
            'warranty_until' => 'garantija līdz',
        ];

    }

    /**
     * Ko dara: Vienoti pieprasījumu statusu nosaukumi Blade skatījumiem un filtriem.
     *
     * Kā strādā: Tehniskos pieteikumu statusus sasaista ar latviskiem tekstiem, ko var droši rādīt Blade skatos.
     *
     * Kad pielietojas: Remonta, norakstīšanas un nodošanas pieteikumu sarakstos, filtros un detaļu skatā.
     */
    protected function requestStatusLabels(): array
    {
        return [
            'submitted' => 'Iesniegts',
            'approved' => 'Apstiprināts',
            'rejected' => 'Noraidīts',
        ];
    }

    /**
     * Ko dara: Izveido remonta ierakstu, pirms tam izlīdzinot datumus legacy shēmām.
     *
     * Kā strādā: Pirms `Repair::create()` izsaukšanas payload izlaiž caur `normalizeRepairPayloadForPersistence()`, lai datumu lauki būtu saderīgi ar datubāzes shēmu.
     *
     * Kad pielietojas: Visās vietās, kur kontrolieri izveido jaunu remonta ierakstu no pieteikuma vai manuālas formas.
     */
    protected function createRepairRecord(array $payload): Repair
    {
        return Repair::create($this->normalizeRepairPayloadForPersistence($payload));
    }

    /**
     * Ko dara: Remonta payload pielāgo kolonnām, kuras dažās vidēs var nepieļaut NULL datumus.
     *
     * Kā strādā: Pārbauda, vai `start_date` un `end_date` kolonnas atļauj NULL; ja neatļauj, tukšu datumu aizstāj ar drošu tehnisku datumu.
     *
     * Kad pielietojas: Pirms remonta saglabāšanas vidēs, kur datubāzes shēma var atšķirties no jaunākajām migrācijām.
     */
    protected function normalizeRepairPayloadForPersistence(array $payload): array
    {
        $status = (string) ($payload['status'] ?? 'waiting');
        $today = now()->toDateString();

        // Dažās vecākās datubāzēs remonta datumu kolonnas nav nullable.
        // Ja lietotājs datumu vēl nav norādījis, ieliekam drošu tehnisku vērtību, lai saglabāšana neizkristu.
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
     * Ko dara: Nolasa, vai remonta tabulas konkrētā datuma kolonna atļauj NULL vērtības.
     *
     * Kā strādā: Pirmā izsaukuma laikā nolasa `repairs` tabulas kolonnu metadatus un tos saglabā statiskā kešatmiņā šī pieprasījuma ietvaros.
     *
     * Kad pielietojas: Kad remonta saglabāšanas loģikai jāzina, vai konkrētu datuma lauku drīkst atstāt tukšu.
     */
    protected function repairColumnAllowsNull(string $column): bool
    {
        static $repairsColumnNullability = null;

        if ($repairsColumnNullability === null) {
            // Kolonnu struktūru nolasām tikai vienu reizi pieprasījuma laikā,
            // jo šī pārbaude var tikt izsaukta vairākās remonta saglabāšanas vietās.
            $repairsColumnNullability = collect(Schema::getColumns('repairs'))
                ->mapWithKeys(fn (array $definition) => [
                    (string) ($definition['name'] ?? '') => (bool) ($definition['nullable'] ?? false),
                ])
                ->all();
        }

        return (bool) ($repairsColumnNullability[$column] ?? true);
    }
}
