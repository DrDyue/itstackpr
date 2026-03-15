<?php

namespace App\Support;

use App\Models\Device;
use App\Models\DeviceType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DeviceImageAutoFetcher
{
    public function __construct(private readonly DeviceAssetManager $assetManager)
    {
    }

    public function populate(Device $device): bool
    {
        if (! config('devices.auto_image_enabled', true) || filled($device->device_image_url)) {
            return false;
        }

        $imageUrl = $this->preview([
            'manufacturer' => $device->manufacturer,
            'model' => $device->model,
            'name' => $device->name,
            'device_type_id' => $device->device_type_id,
        ], $device->relationLoaded('type') ? $device->type : null);

        if (! $imageUrl) {
            return false;
        }

        $storedPath = $this->storeFromUrl($imageUrl, $device->device_image_url);

        if (! $storedPath) {
            return false;
        }

        $device->forceFill(['device_image_url' => $storedPath])->save();

        return true;
    }

    public function preview(array $attributes, ?DeviceType $deviceType = null): ?string
    {
        foreach ($this->searchQueries($attributes, $deviceType) as $query) {
            $imageUrl = $this->findImageUrl($query);

            if ($imageUrl) {
                return $imageUrl;
            }
        }

        return null;
    }

    public function storeFromUrl(string $imageUrl, ?string $previousPath = null): ?string
    {
        return $this->downloadAndStore($imageUrl, $previousPath);
    }

    private function searchQueries(array $attributes, ?DeviceType $deviceType = null): array
    {
        $parts = array_values(array_filter([
            trim((string) ($attributes['manufacturer'] ?? '')),
            trim((string) ($attributes['model'] ?? '')),
            trim((string) ($attributes['name'] ?? '')),
            trim((string) ($deviceType?->type_name ?? '')),
        ]));

        $queries = [
            implode(' ', array_slice($parts, 0, 3)),
            implode(' ', array_slice($parts, 0, 2)),
            implode(' ', array_slice($parts, 1, 2)),
            implode(' ', array_slice($parts, 0, 1)),
            implode(' ', array_slice($parts, 2, 1)),
        ];

        return array_values(array_unique(array_filter($queries, fn (string $query) => $query !== '')));
    }

    private function findImageUrl(string $query): ?string
    {
        try {
            $response = Http::timeout((int) config('devices.auto_image_timeout', 4))
                ->retry(1, 200)
                ->acceptJson()
                ->withUserAgent((string) config('devices.auto_image_user_agent', 'ITStackPR Device Image Fetcher/1.0'))
                ->get('https://en.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'generator' => 'search',
                    'gsrsearch' => $query,
                    'gsrlimit' => (int) config('devices.auto_image_candidates', 3),
                    'prop' => 'pageimages',
                    'piprop' => 'original|thumbnail',
                    'pithumbsize' => 1200,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $pages = collect($response->json('query.pages', []))
                ->sortBy(fn (array $page) => $page['index'] ?? PHP_INT_MAX)
                ->values();

            foreach ($pages as $page) {
                $source = data_get($page, 'original.source') ?: data_get($page, 'thumbnail.source');

                if (is_string($source) && Str::startsWith($source, ['http://', 'https://'])) {
                    return $source;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function downloadAndStore(string $imageUrl, ?string $previousPath = null): ?string
    {
        try {
            $response = Http::timeout((int) config('devices.auto_image_timeout', 4))
                ->retry(1, 200)
                ->withUserAgent((string) config('devices.auto_image_user_agent', 'ITStackPR Device Image Fetcher/1.0'))
                ->get($imageUrl);

            if (! $response->ok()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if (! Str::startsWith($contentType, 'image/')) {
                return null;
            }

            $contents = $response->body();
            if ($contents === '') {
                return null;
            }

            return $this->assetManager->storeDeviceImageContents(
                $contents,
                $this->extensionFromResponse($imageUrl, $contentType),
                $previousPath
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function extensionFromResponse(string $imageUrl, string $contentType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (isset($map[$contentType])) {
            return $map[$contentType];
        }

        $path = parse_url($imageUrl, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        return $extension !== '' ? $extension : null;
    }
}
