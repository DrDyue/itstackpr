<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\WriteoffRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Ko dara: Sagatavo administratora darba virsmas datus.
 *
 * Kā strādā: Apvieno ierīču statistiku, telpu koku, filtrēto ierīču tabulu, ātrās darbības un gaidošo pieteikumu priekšskatījumus.
 *
 * Kad pielietojas: Kad administrators vai IT vadītājs atver darba virsmu vai asinhroni filtrē tajā redzamo ierīču tabulu.
 */
class DashboardController extends Controller
{
    /**
     * Ko dara: Parāda darba virsmu vai parasto lietotāju novirza uz viņa ierīcēm.
     *
     * Kā strādā: Administrators un IT vadītājs redz pilno darba virsmu ar statistiku, telpu koku un ierīču tabulu. Parasts lietotājs automātiski tiek novirzīts uz savu ierīču sarakstu.
     *
     * Kad pielietojas: Izsaukšana: GET /dashboard | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Pēc pieslēgšanās vai klikšķinot uz "Darba virsma" sānjoslā.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $user->canManageRequests()) {
            return redirect()->route('devices.index');
        }

        return $this->renderDashboard($request);
    }

    /**
     * Ko dara: Atgriež filtrētu ierīču tabulu priekš dashboard (async).
     *
     * Kā strādā: Atjaunina tikai tabulas HTML fragmentu bez pilnas lapas pārlādēšanas, kad administrators maina stāvu vai telpu filtru darba virsmas sānjoslā.
     *
     * Kad pielietojas: Izsaukšana: GET /dashboard/devices | Pieejams: administrators, IT vadītājs. Scenārijs: JavaScript izsauc šo maršrutu, kad lietotājs izvēlas citu stāvu vai telpu darba virsmas atrašanās vietu kokā.
     */
    public function devices(Request $request): View
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canManageRequests(), 403);

        $filters = $this->dashboardFilters($request);
        $sorting = $this->dashboardSorting($request);
        $viewData = $this->dashboardDevicesData($filters, null, $sorting, $user->prefersHiddenWrittenOffDevices());

        return view('dashboard.devices-table', [
            'dashboardDevices' => $viewData['dashboardDevices'],
            'dashboardDeviceCount' => $viewData['dashboardDeviceCount'],
            'dashboardDeviceStates' => $viewData['dashboardDeviceStates'],
            'filters' => $filters,
            'sorting' => $sorting,
            'sortOptions' => $this->dashboardSortOptions(),
            'sortDirectionLabels' => $this->sortDirectionLabels(),
        ]);
    }

    /**
     * Ko dara: Sagatavo visus nepieciešamos datus darba virsmas skatam.
     *
     * Kā strādā: Apvieno ierīču datus, telpu koku un ātro darbību kartītes vienā masīvā.
     *
     * Kad pielietojas: Izsauc no: `renderDashboard()`.
     */
    private function dashboardViewData(Request $request, $user, array $filters): array
    {
        $isManager = $user->canManageRequests();
        $hasRooms = $this->featureTableExists('rooms');
        $hideWrittenOffDevices = $user->prefersHiddenWrittenOffDevices();
        $sorting = $this->dashboardSorting($request);
        // Dažas funkciju tabulas vecākās instalācijās var vēl neeksistēt,
        // tāpēc pirms vaicājumu veidošanas pārbaudām to esamību.
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;

        $locationRooms = $this->dashboardLocationRooms($hasRooms && $isManager, $hideWrittenOffDevices);
        $locationTree = $this->dashboardLocationTree($locationRooms, $filters);

        $pendingRepairRequestCount = $repairRequestQuery
            ? (clone $repairRequestQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count()
            : 0;

        $pendingWriteoffRequestCount = $writeoffRequestQuery
            ? (clone $writeoffRequestQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count()
            : 0;

        return array_merge($this->dashboardDevicesData($filters, $locationRooms, $sorting, $hideWrittenOffDevices), [
            'locationTree' => $locationTree,
            'quickActions' => $this->quickActions(
                $pendingRepairRequestCount,
                $pendingWriteoffRequestCount
            ),
            'filters' => $filters,
        ]);
    }

    /**
     * Ko dara: Renderē pilno darba virsmas skatu ar filtriem, kārtošanu un ierīču tabulu.
     *
     * Kā strādā: Nodod sagatavoto Blade skatu ar visiem darba virsmai nepieciešamajiem datiem.
     *
     * Kad pielietojas: Izsauc no: `index()` — tikai tad, ja lietotājs ir administrators vai IT vadītājs.
     */
    public function renderDashboard(Request $request): View
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canManageRequests(), 403);

        $filters = $this->dashboardFilters($request);
        $sorting = $this->dashboardSorting($request);
        $viewData = $this->dashboardViewData($request, $user, $filters);

        return view('dashboard', array_merge($viewData, [
            'user' => $user,
            'isManager' => true,
            'filters' => $filters,
            'sorting' => $sorting,
            'sortOptions' => $this->dashboardSortOptions(),
            'sortDirectionLabels' => $this->sortDirectionLabels(),
        ]));
    }

    /**
     * Ko dara: Normalizē filtru parametrus (stāvs, telpa) no URL vaicājuma.
     *
     * Kā strādā: No URL nolasa tikai `floor` un `room_id`, apgriež atstarpes un atgriež vienotu filtru masīvu dashboard vaicājumiem.
     *
     * Kad pielietojas: Izsauc no: `renderDashboard()`, `devices()`.
     */
    private function dashboardFilters(Request $request): array
    {
        return [
            'floor' => trim((string) $request->query('floor', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
        ];
    }

    /**
     * Ko dara: Normalizē kārtošanas parametrus (kolonna, virziens) darba virsmas ierīču tabulai.
     *
     * Kā strādā: Pārbauda, vai pieprasītais lauks ir atļautajās dashboard kolonnās, normalizē virzienu un sagatavo label tekstu skatam.
     *
     * Kad pielietojas: Izsauc no: `renderDashboard()`, `devices()`, `dashboardViewData()`.
     */
    private function dashboardSorting(Request $request): array
    {
        $sortOptions = $this->dashboardSortOptions();
        $sort = trim((string) $request->query('sort', 'created_at'));
        $direction = trim((string) $request->query('direction', 'desc'));

        if (! array_key_exists($sort, $sortOptions)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'created_at' ? 'desc' : 'asc';
        }

        if ($sort === 'created_at' && ! $request->has('direction')) {
            $direction = 'desc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $sortOptions[$sort]['label'] ?? 'izveides datuma',
        ];
    }

    /**
     * Ko dara: Ielādē telpas ar ierīču skaitu darba virsmas telpu koka atveidošanai.
     *
     * Kā strādā: Ja `shouldLoad` ir false (parastam lietotājam), atgriež tukšu kolekciju.
     *
     * Kad pielietojas: Izsauc no: `dashboardViewData()`.
     */
    private function dashboardLocationRooms(bool $shouldLoad, bool $hideWrittenOffDevices = false): Collection
    {
        if (! $shouldLoad) {
            return collect();
        }

        return Room::query()
            ->select(['id', 'building_id', 'floor_number', 'room_number', 'room_name', 'department'])
            ->with(['building:id,building_name'])
            ->when(
                $hideWrittenOffDevices,
                fn (Builder $query) => $query->whereHas(
                    'devices',
                    fn (Builder $deviceQuery) => $deviceQuery->where('status', '!=', Device::STATUS_WRITEOFF)
                )
            )
            ->withCount([
                'devices' => fn (Builder $query) => $query->when(
                    $hideWrittenOffDevices,
                    fn (Builder $deviceQuery) => $deviceQuery->where('status', '!=', Device::STATUS_WRITEOFF)
                ),
            ])
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
    }

    /**
     * Ko dara: Pārveido telpu kolekciju hierarhiskā stāvu/telpu kokā Blade skatam.
     *
     * Kā strādā: Grupē telpas pēc stāva un pievieno katrai rindai aktīvās filtrēšanas stāvokli.
     *
     * Kad pielietojas: Izsauc no: `dashboardViewData()`.
     */
    private function dashboardLocationTree(Collection $locationRooms, array $filters): Collection
    {
        return $locationRooms
            ->groupBy(fn (Room $room) => (string) ($room->floor_number ?? 0))
            ->sortKeys()
            ->map(function ($rooms, $floorKey) use ($filters) {
                return [
                    'id' => (string) $floorKey,
                    'label' => ((int) $floorKey).'. stāvs',
                    'room_count' => $rooms->count(),
                    'device_count' => (int) $rooms->sum('devices_count'),
                    'rooms' => $rooms->map(function (Room $room) use ($filters) {
                        return [
                            'id' => $room->id,
                            'room_number' => $room->room_number,
                            'room_name' => $room->room_name,
                            'building_name' => $room->building?->building_name,
                            'department' => $room->department,
                            'device_count' => (int) $room->devices_count,
                            'is_active' => (string) $room->id === $filters['room_id'],
                        ];
                    })->values(),
                    'is_active' => (string) $floorKey === $filters['floor'] && $filters['room_id'] === '',
                ];
            })
            ->values();
    }

    /**
     * Ko dara: Ielādē darba virsmas ierīču tabulas datus ar statusu priekšskatījumiem.
     *
     * Kā strādā: Pielieto filtrēšanu un kārtošanu, pēc tam sagatavo katras ierīces stāvokļa masīvu ar remonta statusu un gaidošo pieprasījumu žetoniem.
     *
     * Kad pielietojas: Izsauc no: `dashboardViewData()`, `devices()`.
     */
    private function dashboardDevicesData(array $filters, ?Collection $locationRooms = null, ?array $sorting = null, bool $hideWrittenOffDevices = false): array
    {
        // Ja ierīču tabula nav pieejama, informācijas panelis atgriež tukšu
        // kolekciju un neaptur visas sākumlapas ielādi.
        if (! $this->featureTableExists('devices')) {
            return [
                'dashboardDevices' => collect(),
                'dashboardDeviceCount' => 0,
                'dashboardDeviceStates' => [],
            ];
        }

        $sorting ??= [
            'sort' => 'created_at',
            'direction' => 'desc',
            'label' => 'izveides datuma',
        ];

        // Pievienojam telpu, ēku un lietotāju tabulas, lai dashboard sarakstu
        // var kārtot pēc redzamiem nosaukumiem, ne tikai pēc ID laukiem.
        $deviceQuery = Device::query()
            ->leftJoin('rooms as sort_rooms', 'sort_rooms.id', '=', 'devices.room_id')
            ->leftJoin('buildings as sort_buildings', 'sort_buildings.id', '=', 'devices.building_id')
            ->leftJoin('users as sort_users', 'sort_users.id', '=', 'devices.assigned_to_id')
            ->when(
                $hideWrittenOffDevices,
                fn (Builder $query) => $query->where('devices.status', '!=', Device::STATUS_WRITEOFF)
            );
        $this->applyDashboardDeviceFilters($deviceQuery, $filters, $locationRooms);
        $this->applyDashboardDeviceSorting($deviceQuery, $sorting);

        // Atlasām tikai dashboard vajadzīgos laukus un relācijas, lai rindām
        // varētu parādīt atrašanās vietu, atbildīgo un aktīvos pieprasījumus.
        $dashboardDevices = $deviceQuery
            ->select([
                'devices.id',
                'devices.code',
                'devices.name',
                'devices.device_type_id',
                'devices.model',
                'devices.status',
                'devices.building_id',
                'devices.room_id',
                'devices.assigned_to_id',
                'devices.serial_number',
                'devices.manufacturer',
                'devices.device_image_url',
                'devices.created_at',
            ])
            ->with([
                'room:id,building_id,room_number,room_name',
                'room.building:id,building_name',
                'building:id,building_name',
                'type:id,type_name',
                'assignedTo:id,full_name,job_title',
                'activeRepair',
                'activeRepair.acceptedBy:id,full_name',
                'activeRepair.request:id,responsible_user_id,reviewed_by_user_id',
                'activeRepair.request.responsibleUser:id,full_name',
                'pendingRepairRequest',
                'pendingRepairRequest.responsibleUser:id,full_name',
                'pendingWriteoffRequest',
                'pendingWriteoffRequest.responsibleUser:id,full_name',
                'pendingTransferRequest',
                'pendingTransferRequest.responsibleUser:id,full_name',
                'pendingTransferRequest.transferTo:id,full_name',
            ])
            ->latest('devices.id')
            ->get();

        // UI stāvokļus sagatavojam pēc ierīces ID, lai Blade skats var ātri
        // atrast remonta statusu, priekšskatījumu un gaidošā pieprasījuma badge.
        $dashboardDeviceStates = $dashboardDevices
            ->mapWithKeys(fn (Device $device) => [
                $device->id => [
                    'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
                    'repairPreview' => $this->repairPreview($device),
                    'pendingRequestBadge' => $this->pendingRequestBadge($device),
                ],
            ])
            ->all();

        return [
            'dashboardDevices' => $dashboardDevices,
            'dashboardDeviceCount' => $dashboardDevices->count(),
            'dashboardDeviceStates' => $dashboardDeviceStates,
        ];
    }

    /**
     * Ko dara: Uzliek stāva un telpas filtrēšanas nosacījumus darba virsmas ierīču vaicājumam.
     *
     * Kā strādā: Ja norādīta konkrēta telpa, filtrē pēc tās; ja norādīts stāvs, izmanto jau ielādētās telpas vai `whereHas` vaicājumu pret telpas stāvu.
     *
     * Kad pielietojas: Izsauc no: `dashboardDevicesData()`.
     */
    private function applyDashboardDeviceFilters($deviceQuery, array $filters, ?Collection $locationRooms = null): void
    {
        if ($filters['room_id'] !== '' && ctype_digit($filters['room_id'])) {
            $deviceQuery->where('devices.room_id', (int) $filters['room_id']);
            return;
        }

        if ($filters['floor'] === '' || ! ctype_digit($filters['floor'])) {
            return;
        }

        if ($locationRooms instanceof Collection && $locationRooms->isNotEmpty()) {
            $roomIds = $locationRooms
                ->filter(fn (Room $room) => (int) $room->floor_number === (int) $filters['floor'])
                ->pluck('id')
                ->all();

            if ($roomIds === []) {
                $deviceQuery->whereRaw('1 = 0');
                return;
            }

            $deviceQuery->whereIn('devices.room_id', $roomIds);
            return;
        }

        $deviceQuery->whereHas('room', fn ($roomQuery) => $roomQuery->where('floor_number', (int) $filters['floor']));
    }

    /**
     * Ko dara: Uzliek kārtošanu darba virsmas ierīču vaicājumam.
     *
     * Kā strādā: Atkarībā no izvēlētās kolonnas pievieno vajadzīgos ORDER BY nosacījumus, tostarp atrašanās vietas, lietotāja un statusa īpašo kārtošanu.
     *
     * Kad pielietojas: Izsauc no: `dashboardDevicesData()`.
     */
    private function applyDashboardDeviceSorting(Builder $query, array $sorting): void
    {
        $direction = ($sorting['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sorting['sort'] ?? 'created_at') {
            'code' => $query
                ->orderByRaw('LOWER(COALESCE(devices.code, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'name' => $query
                ->orderByRaw('LOWER(COALESCE(devices.name, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'location' => $query
                ->orderByRaw('LOWER(COALESCE(sort_buildings.building_name, "")) ' . $direction)
                ->orderBy('sort_rooms.floor_number', $direction)
                ->orderByRaw('LOWER(COALESCE(sort_rooms.room_number, "")) ' . $direction)
                ->orderByRaw('LOWER(COALESCE(sort_rooms.room_name, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'assigned_to' => $query
                ->orderByRaw('LOWER(COALESCE(sort_users.full_name, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'status' => $query
                ->orderByRaw($this->dashboardDeviceStatusSortExpression() . ' ' . $direction)
                ->orderBy('devices.id', $direction),
            default => $query
                ->orderBy('devices.created_at', $direction)
                ->orderBy('devices.id', $direction),
        };
    }

    /**
     * Ko dara: Atgriež SQL CASE izteiksmi ierīces statusa kārtošanai prioritātes secībā.
     *
     * Kā strādā: Atgriež SQL CASE fragmentu, kur aktīvas ierīces tiek kārtotas pirms remonta un norakstītām ierīcēm.
     *
     * Kad pielietojas: Izsauc no: `applyDashboardDeviceSorting()` — kad kārtošanas kolonna ir "status".
     */
    private function dashboardDeviceStatusSortExpression(): string
    {
        return <<<'SQL'
CASE
    WHEN devices.status = 'active' THEN 1
    WHEN devices.status = 'repair' THEN 2
    WHEN devices.status = 'writeoff' THEN 3
    ELSE 4
END
SQL;
    }

    /**
     * Ko dara: Atgriež darba virsmas ierīču tabulas kārtošanas lauku nosaukumu karti.
     *
     * Kā strādā: Definē atļautās dashboard tabulas kārtošanas kolonnas un katrai piešķir cilvēkam saprotamu latvisku label.
     *
     * Kad pielietojas: Izsauc no: `devices()`, `renderDashboard()`, `dashboardSorting()`.
     */
    private function dashboardSortOptions(): array
    {
        return [
            'created_at' => ['label' => 'izveides datuma'],
            'code' => ['label' => 'koda'],
            'name' => ['label' => 'ierīces nosaukuma'],
            'location' => ['label' => 'atrašanās vietas'],
            'assigned_to' => ['label' => 'piešķirtā lietotāja'],
            'status' => ['label' => 'statusa'],
        ];
    }

    /**
     * Ko dara: Atgriež kārtošanas virzienu cilvēkam saprotamo nosaukumu karti.
     *
     * Kā strādā: Tehniskās vērtības `asc` un `desc` pārvērš tekstos, ko var izmantot pogās, auditā un aktīvā kārtojuma aprakstā.
     *
     * Kad pielietojas: Izsauc no: `devices()`, `renderDashboard()`.
     */
    private function sortDirectionLabels(): array
    {
        return [
            'asc' => 'augošajā secībā',
            'desc' => 'dilstošajā secībā',
        ];
    }

    /**
     * Ko dara: Definē ātrās darbības kartītes darba virsmas augšdaļai.
     *
     * Kā strādā: Katrai kartītei iestata etiķeti, ikonu, URL un gaidošo pieprasījumu skaitu.
     *
     * Kad pielietojas: Izsauc no: `dashboardViewData()`.
     */
    private function quickActions(int $pendingRepairRequestCount, int $pendingWriteoffRequestCount): array
    {
        return [
            [
                'label' => 'Jauna ierīce',
                'url' => route('devices.index', ['device_modal' => 'create']),
                'icon' => 'plus',
                'class' => 'btn-create',
                'count' => null,
            ],
            [
                'label' => 'Pievienot remontu',
                'url' => route('repairs.index', ['repair_modal' => 'create']),
                'icon' => 'repair',
                'class' => 'btn-edit',
                'count' => null,
            ],
            [
                'label' => 'Remonta pieteikumi',
                'url' => route('repair-requests.index'),
                'icon' => 'repair-request',
                'class' => 'btn-view',
                'count' => $pendingRepairRequestCount,
            ],
            [
                'label' => 'Norakstīšanas pieteikumi',
                'url' => route('writeoff-requests.index'),
                'icon' => 'writeoff',
                'class' => 'btn-danger',
                'count' => $pendingWriteoffRequestCount,
            ],
        ];
    }

    /**
     * Ko dara: Pārveido remonta tehnisko statusu cilvēkam saprotamā birkā.
     *
     * Kā strādā: Ar `match` izteiksmi remonta statusus `waiting` un `in-progress` pārvērš īsās latviskās birkās; citiem statusiem birku nerāda.
     *
     * Kad pielietojas: Izsauc no: `visibleRepairStatusLabel()`, `repairPreview()`.
     */
    public function repairStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            default => null,
        };
    }

    /**
     * Ko dara: Aprēķina, kādu remonta apakšstatusu rādīt ierīcei dashboardā.
     *
     * Kā strādā: Pārbauda ierīces aktīvo remontu un atgriež statusa birku; ja statuss nav atpazīts, izmanto drošu noklusējumu "Gaida".
     *
     * Kad pielietojas: Izsauc no: `dashboardDevicesData()` — iekļauts katrā ierīces stāvokļa masīvā.
     */
    public function visibleRepairStatusLabel(Device $device): ?string
    {
        if (! $device->activeRepair) {
            return null;
        }

        return $this->repairStatusLabel($device->activeRepair->status) ?: 'Gaida';
    }

    /**
     * Ko dara: Sagatavo remonta hover priekšskatījuma saturu.
     *
     * Kā strādā: Atgriež masīvu ar remonta ieraksta detaļām, kuru JavaScript parāda kā peldošo pārskatu, virzot peli pār remonta birku ierīces rindā.
     *
     * Kad pielietojas: Izsauc no: `dashboardDevicesData()` — iekļauts katrā ierīces stāvokļa masīvā.
     */
    public function repairPreview(Device $device): ?array
    {
        if (! $device->activeRepair) {
            return null;
        }

        $repair = $device->activeRepair;

        return [
            'title' => 'Remonta ieraksts',
            'status' => $this->repairStatusLabel($repair->status) ?: 'Gaida',
            'type' => $repair->repair_type === 'external' ? 'Ārējais' : 'Iekšējais',
            'approved_by' => $repair->approval_actor_name
                ?: $repair->request?->responsibleUser?->full_name
                ?: '-',
            'created_at' => $repair->created_at?->format('d.m.Y H:i') ?: '-',
            'description' => $repair->description ?: 'Apraksts nav pievienots.',
        ];
    }

    /**
     * Ko dara: Sagatavo informāciju par gaidošo pieprasījumu birku dashboard tabulā.
     *
     * Kā strādā: Pārbauda secībā: remonta → norakstīšanas → nodošanas pieprasījums. Atgriež pirmā atrastā pieprasījuma birkas datus vai null, ja pieprasījumu nav.
     *
     * Kad pielietojas: Izsauc no: `dashboardDevicesData()` — iekļauts katrā ierīces stāvokļa masīvā.
     */
    public function pendingRequestBadge(Device $device): ?array
    {
        if ($device->pendingRepairRequest) {
            return [
                'icon' => 'repair-request',
                'label' => 'Apskatīt',
                'detail_label' => 'Remonts',
                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                'url' => $this->requestIndexUrl($device, 'repair', $device->pendingRepairRequest?->id),
                'preview' => $this->pendingRequestPreview('repair', $device->pendingRepairRequest),
            ];
        }

        if ($device->pendingWriteoffRequest) {
            return [
                'icon' => 'writeoff',
                'label' => 'Apskatīt',
                'detail_label' => 'Norakst.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'url' => $this->requestIndexUrl($device, 'writeoff', $device->pendingWriteoffRequest?->id),
                'preview' => $this->pendingRequestPreview('writeoff', $device->pendingWriteoffRequest),
            ];
        }

        if ($device->pendingTransferRequest) {
            return [
                'icon' => 'transfer',
                'label' => 'Apskatīt',
                'detail_label' => 'Nodošana',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'url' => $this->requestIndexUrl($device, 'transfer', $device->pendingTransferRequest?->id),
                'preview' => $this->pendingRequestPreview('transfer', $device->pendingTransferRequest),
            ];
        }

        return null;
    }

    /**
     * Ko dara: Ģenerē saiti uz attiecīgā pieprasījuma sarakstu ar izcēlumu un filtru parametriem.
     *
     * Kā strādā: Papildina URL ar enkuru un koda vai nosaukuma parametriem, lai pārlūks automātiski ritinātu un iezīmētu mērķa pieprasījumu sarakstā.
     *
     * Kad pielietojas: Izsauc no: `pendingRequestBadge()`.
     */
    private function requestIndexUrl(Device $device, string $type, ?int $requestId = null): ?string
    {
        $params = [
            'statuses_filter' => 1,
            'status' => ['submitted'],
        ];

        if ($requestId) {
            $params['highlight'] = $device->code ?: $device->name;
            $params['highlight_mode'] = $device->code ? 'exact' : 'contains';
            $params['highlight_id'] = match ($type) {
                'repair' => 'repair-request-' . $requestId,
                'writeoff' => 'writeoff-request-' . $requestId,
                'transfer' => 'device-transfer-' . $requestId,
                default => null,
            };
        }

        $baseUrl = match ($type) {
            'repair' => Route::has('repair-requests.index') ? route('repair-requests.index', $params) : null,
            'writeoff' => Route::has('writeoff-requests.index') ? route('writeoff-requests.index', $params) : null,
            'transfer' => Route::has('device-transfers.index') ? route('device-transfers.index', $params) : null,
            default => null,
        };

        if (! $baseUrl || ! $requestId) {
            return $baseUrl;
        }

        $anchor = match ($type) {
            'repair' => 'repair-request-',
            'writeoff' => 'writeoff-request-',
            'transfer' => 'device-transfer-',
            default => '',
        };

        return $anchor !== '' ? $baseUrl . '#' . $anchor . $requestId : $baseUrl;
    }

    /**
     * Ko dara: Sagatavo gaidošā pieprasījuma hover priekšskatījuma saturu pēc tipa.
     *
     * Kā strādā: Atgriež masīvu ar pieprasījuma metadatiem (iesniedzējs, datums, kopsavilkums), kuru JavaScript parāda kā peldošo pārskatu virs birkas.
     *
     * Kad pielietojas: Izsauc no: `pendingRequestBadge()`.
     */
    private function pendingRequestPreview(string $type, mixed $request): ?array
    {
        if (! $request) {
            return null;
        }

        return match ($type) {
            'repair' => [
                'type_label' => 'Remonta pieprasījums',
                'meta_label' => 'Apraksts',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->description ?: 'Apraksts nav pievienots.',
                'recipient' => null,
            ],
            'writeoff' => [
                'type_label' => 'Norakstīšanas pieprasījums',
                'meta_label' => 'Iemesls',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->reason ?: 'Iemesls nav pievienots.',
                'recipient' => null,
            ],
            'transfer' => [
                'type_label' => 'Nodošanas pieprasījums',
                'meta_label' => 'Iemesls',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->transfer_reason ?: 'Iemesls nav pievienots.',
                'recipient' => $request->transferTo?->full_name ?: null,
            ],
            default => null,
        };
    }
}
