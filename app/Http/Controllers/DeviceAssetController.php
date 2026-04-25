<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Droša ierīču attēlu atdošana.
 *
 * Kontrolieris pārbauda, vai lietotājs drīkst skatīt konkrēto ierīci,
 * un tikai tad atdod lokālo vai attālo attēlu.
 */
class DeviceAssetController extends Controller
{
    /**
     * Atgriež lokāli glabātu ierīces attēlu pēc drošības pārbaudes.
     *
     * Pārbauda, vai pierakstītais lietotājs drīkst piekļūt šai ierīcei,
     * un tikai tad atdod attēlu no local storage diska.
     *
     * Izsaukšana: GET /device-assets/{path} | Pieejams: autentificēts, ja ir pieejama ierīce.
     * Scenārijs: Pārlūks pieprasīja ierīces attēlu, kura URL satur resursa ceļu.
     */
    public function show(string $path)
    {
        abort_unless($this->canViewStoredAsset($path), 404);

        $disk = Storage::disk((string) config('devices.asset_disk', 'public'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }

    /**
     * Starpnieko attāla attēla drošu priekšskatījumu ar drošības pārbaudēm.
     *
     * Validē attēla URL, pārbauda pieejamību un pagriežana pierakstītajam lietotājam.
     * Attēli tiek iegūti ar lietotāja agenta nosaukumu un tiek pārbaudīti MIME tipi.
     *
     * Izsaukšana: GET /device-assets/remote-preview?url=... | Pieejams: autentificēts.
     * Scenārijs: Pārlūks jautā par attāla sīkdatņu priekšskatījuma attēlu ar URL parametru.
     */
    public function remotePreview(Request $request)
    {
        $url = (string) $request->query('url', '');

        abort_unless($this->isAllowedRemoteUrl($url), 404);
        abort_unless($this->canViewRemoteAsset($url), 404);

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

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    private function canViewStoredAsset(string $path): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $device = $this->findDeviceByAssetPath($path);

        return $device !== null && $user->canViewDevice($device);
    }

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

    private function findDeviceByAssetPath(string $path): ?Device
    {
        $basename = strtolower(basename($path));

        return Device::query()
            ->where(function ($query) use ($path, $basename) {
                $query->where('device_image_url', $path)
                    ->orWhereRaw('LOWER(device_image_url) LIKE ?', ['%/' . $basename]);
            })
            ->first();
    }
}
