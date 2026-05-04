<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ko dara: Droši atdod ierīču attēlus lietotājiem.
 *
 * Kā strādā: Pārbauda piekļuves tiesības konkrētajai ierīcei un tikai pēc tam atdod lokālu attēlu vai attāla attēla priekšskatījumu.
 *
 * Kad pielietojas: Kad pārlūks pieprasa ierīces attēlu ierīču sarakstā, detalizētajā skatā vai attēla priekšskatījumā.
 */
class DeviceAssetController extends Controller
{
    /**
     * Ko dara: Atgriež lokāli glabātu ierīces attēlu pēc drošības pārbaudes.
     *
     * Kā strādā: Pārbauda, vai pierakstītais lietotājs drīkst piekļūt šai ierīcei, un tikai tad atdod attēlu no local storage diska.
     *
     * Kad pielietojas: Izsaukšana: GET /device-assets/{path} | Pieejams: autentificēts, ja ir pieejama ierīce. Scenārijs: Pārlūks pieprasīja ierīces attēlu, kura URL satur resursa ceļu.
     */
    public function show(string $path)
    {
        // Pirms faila atdošanas pārbaudām ne tikai paša faila esamību,
        // bet arī to, vai konkrētais lietotājs drīkst redzēt ar to saistīto ierīci.
        abort_unless($this->canViewStoredAsset($path), 404);

        $disk = Storage::disk((string) config('devices.asset_disk', 'public'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }

    /**
     * Ko dara: Starpnieko attāla attēla drošu priekšskatījumu ar drošības pārbaudēm.
     *
     * Kā strādā: Validē attēla URL, pārbauda pieejamību un pagriežana pierakstītajam lietotājam. Attēli tiek iegūti ar lietotāja agenta nosaukumu un tiek pārbaudīti MIME tipi.
     *
     * Kad pielietojas: Izsaukšana: GET /device-assets/remote-preview?url=... | Pieejams: autentificēts. Scenārijs: Pārlūks jautā par attāla sīkdatņu priekšskatījuma attēlu ar URL parametru.
     */
    public function remotePreview(Request $request)
    {
        $url = (string) $request->query('url', '');

        abort_unless($this->isAllowedRemoteUrl($url), 404);
        abort_unless($this->canViewRemoteAsset($url), 404);

        // Attālo priekšskatījumu ielādē serveris, nevis pārlūks tieši,
        // lai mēs saglabātu piekļuves kontroli un varētu pārbaudīt saturu.
        $response = Http::timeout(12)
            ->retry(1, 250)
            ->withUserAgent((string) config('devices.auto_image_user_agent', 'ITStackPR Device Image Fetcher/1.0'))
            ->withHeaders([
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ])
            ->get($url);

        abort_unless($response->ok(), 404);

        $contents = $response->body();
        abort_unless($contents !== '', 404);

        $contentType = strtolower((string) $response->header('Content-Type'));
        $imageInfo = @getimagesizefromstring($contents);

        // Daži avoti nesūta korektu Content-Type galveni, tāpēc kā rezerves pārbaudi
        // nolasām MIME tipu no faktiskā binārā satura.
        if (! Str::startsWith($contentType, 'image/')) {
            $contentType = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
        }

        abort_unless(Str::startsWith($contentType, 'image/'), 404);

        return response($contents, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Ko dara: Pārbauda, vai attālais attēla URL ir drošs ielādei.
     *
     * Kā strādā: Pieļauj tikai http/https adreses, nolasa URL daļas un bloķē localhost, .local domēnus, privātās IP adreses un rezervētos tīklus.
     *
     * Kad pielietojas: Izsauc no: `remotePreview()`.
     */
    private function isAllowedRemoteUrl(string $url): bool
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }

        // Ja host ir IP adrese, bloķējam privātos un rezervētos tīklus,
        // lai attēlu priekšskatījums nevarētu tikt izmantots iekšējo resursu pieprasīšanai.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    /**
     * Ko dara: Pārbauda lietotāja tiesības skatīt lokāli glabātu ierīces attēlu.
     *
     * Kā strādā: Nolasa pašreizējo lietotāju, pēc attēla ceļa atrod saistīto ierīci un izmanto lietotāja `canViewDevice()` tiesību pārbaudi.
     *
     * Kad pielietojas: Izsauc no: `show()`.
     */
    private function canViewStoredAsset(string $path): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $device = $this->findDeviceByAssetPath($path);

        return $device !== null && $user->canViewDevice($device);
    }

    /**
     * Ko dara: Pārbauda lietotāja tiesības skatīt attālu ierīces attēlu.
     *
     * Kā strādā: Pēc attālā URL atrod ierīci, kurai šis URL saglabāts kā attēls, un pārbauda, vai lietotājs drīkst redzēt šo ierīci.
     *
     * Kad pielietojas: Izsauc no: `remotePreview()`.
     */
    private function canViewRemoteAsset(string $url): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $device = Device::query()
            ->where('device_image_url', $url)
            ->first();

        return $device !== null && $user->canViewDevice($device);
    }

    /**
     * Ko dara: Atrod ierīci pēc glabātā attēla ceļa vai faila nosaukuma.
     *
     * Kā strādā: Salīdzina pilno saglabāto ceļu un faila nosaukumu, lai atrastu ierīci arī tad, ja attēls tiek pieprasīts ar storage ceļa variantu.
     *
     * Kad pielietojas: Izsauc no: `canViewStoredAsset()`.
     */
    private function findDeviceByAssetPath(string $path): ?Device
    {
        $basename = strtolower(basename($path));

        // Salīdzinām gan pilno ceļu, gan faila nosaukuma beigas,
        // jo storage URL formāts var atšķirties starp vidi un pieprasījuma ceļu.
        return Device::query()
            ->where(function ($query) use ($path, $basename) {
                $query->where('device_image_url', $path)
                    ->orWhereRaw('LOWER(device_image_url) LIKE ?', ['%/' . $basename]);
            })
            ->first();
    }
}
