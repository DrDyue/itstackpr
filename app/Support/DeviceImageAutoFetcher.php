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
        return $this->previewMany($attributes, $deviceType, 1)[0] ?? null;
    }

    public function previewMany(array $attributes, ?DeviceType $deviceType = null, int $batch = 1, int $perBatch = 3): array
    {
        $batch = max(1, $batch);
        $perBatch = max(1, min($perBatch, 6));
        $context = $this->searchContext($attributes, $deviceType);
        $results = [];
        $searchStage = $this->searchStage($batch);

        foreach ($this->searchQueries($attributes, $deviceType, $searchStage) as $query) {
            foreach ($this->findCommonsImageCandidates($query, $context) as $candidate) {
                $results[$candidate['url']] = $candidate;
            }
        }

        if ($results === []) {
            foreach ($this->searchQueries($attributes, $deviceType, $searchStage) as $query) {
                foreach ($this->findWikipediaImageCandidates($query, $context) as $candidate) {
                    $results[$candidate['url']] = $candidate;
                }
            }
        }

        if ($results === []) {
            return [];
        }

        uasort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_values(array_map(
            fn (array $candidate) => $candidate['url'],
            array_slice($results, 0, $perBatch)
        ));
    }

    public function storeFromUrl(string $imageUrl, ?string $previousPath = null): ?string
    {
        return $this->downloadAndStore($imageUrl, $previousPath);
    }

    private function searchQueries(array $attributes, ?DeviceType $deviceType = null, int $searchStage = 1): array
    {
        $manufacturer = trim((string) ($attributes['manufacturer'] ?? ''));
        $model = trim((string) ($attributes['model'] ?? ''));
        $name = trim((string) ($attributes['name'] ?? ''));
        $type = trim((string) ($deviceType?->type_name ?? ''));

        $queries = match ($searchStage) {
            1 => [
                trim("\"{$manufacturer}\" \"{$model}\""),
                trim("{$manufacturer} {$model}"),
                trim("\"{$manufacturer} {$model}\""),
                trim("{$model} {$manufacturer}"),
            ],
            2 => [
                trim("\"{$manufacturer}\" \"{$model}\" {$name}"),
                trim("{$manufacturer} {$model} {$name}"),
                trim("\"{$model}\" {$name} {$manufacturer}"),
                trim("{$manufacturer} {$name} {$model}"),
            ],
            default => [
                trim("\"{$manufacturer}\" \"{$model}\" {$name} {$type}"),
                trim("{$manufacturer} {$model} {$type}"),
                trim("{$manufacturer} {$name} {$type}"),
                trim("\"{$model}\" {$type} {$manufacturer}"),
                trim("{$name} {$type} {$manufacturer} {$model}"),
            ],
        };

        return array_values(array_unique(array_filter($queries, fn (string $query) => $query !== '')));
    }

    private function searchStage(int $batch): int
    {
        return match (true) {
            $batch <= 1 => 1,
            $batch === 2 => 2,
            default => 3,
        };
    }

    private function findCommonsImageCandidates(string $query, array $context): array
    {
        try {
            $response = Http::timeout((int) config('devices.auto_image_timeout', 4))
                ->retry(1, 200)
                ->acceptJson()
                ->withUserAgent((string) config('devices.auto_image_user_agent', 'ITStackPR Device Image Fetcher/1.0'))
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'generator' => 'search',
                    'gsrsearch' => $query,
                    'gsrnamespace' => 6,
                    'gsrlimit' => min(20, max(9, (int) config('devices.auto_image_candidates', 9))),
                    'prop' => 'imageinfo',
                    'iiprop' => 'url',
                ]);

            if (! $response->ok()) {
                return [];
            }

            $pages = collect($response->json('query.pages', []))
                ->sortBy(fn (array $page) => $page['index'] ?? PHP_INT_MAX)
                ->values();

            $candidates = [];

            foreach ($pages as $page) {
                $source = data_get($page, 'imageinfo.0.url');
                $title = (string) ($page['title'] ?? '');

                if (is_string($source) && Str::startsWith($source, ['http://', 'https://'])) {
                    $candidates[] = [
                        'url' => $source,
                        'score' => $this->scoreCandidate($title, $query, $context) + 20,
                    ];
                }
            }
            return $candidates;
        } catch (Throwable) {
            return [];
        }
    }

    private function findWikipediaImageCandidates(string $query, array $context): array
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
                    'gsrlimit' => min(20, max(9, (int) config('devices.auto_image_candidates', 9))),
                    'prop' => 'pageimages|extracts',
                    'piprop' => 'original|thumbnail',
                    'pithumbsize' => 1200,
                    'exintro' => 1,
                    'explaintext' => 1,
                ]);

            if (! $response->ok()) {
                return [];
            }

            $pages = collect($response->json('query.pages', []))
                ->sortBy(fn (array $page) => $page['index'] ?? PHP_INT_MAX)
                ->values();

            $candidates = [];

            foreach ($pages as $page) {
                $source = data_get($page, 'original.source') ?: data_get($page, 'thumbnail.source');
                $title = (string) ($page['title'] ?? '');
                $extract = (string) ($page['extract'] ?? '');

                if (is_string($source) && Str::startsWith($source, ['http://', 'https://'])) {
                    $candidates[] = [
                        'url' => $source,
                        'score' => $this->scoreCandidate($title . ' ' . $extract, $query, $context),
                    ];
                }
            }

            return $candidates;
        } catch (Throwable) {
            return [];
        }
    }

    private function searchContext(array $attributes, ?DeviceType $deviceType = null): array
    {
        $tokens = collect([
            $attributes['manufacturer'] ?? '',
            $attributes['model'] ?? '',
            $attributes['name'] ?? '',
            $deviceType?->type_name ?? '',
        ])
            ->flatMap(fn (string $value) => preg_split('/[^[:alnum:]]+/u', Str::lower($value)) ?: [])
            ->filter(fn (?string $token) => $token !== null && $token !== '' && strlen($token) >= 2)
            ->values()
            ->all();

        return [
            'tokens' => array_values(array_unique($tokens)),
            'manufacturer' => Str::lower((string) ($attributes['manufacturer'] ?? '')),
            'model' => Str::lower((string) ($attributes['model'] ?? '')),
            'name' => Str::lower((string) ($attributes['name'] ?? '')),
            'type' => Str::lower((string) ($deviceType?->type_name ?? '')),
        ];
    }

    private function scoreCandidate(string $haystack, string $query, array $context): int
    {
        $normalized = Str::lower($haystack);
        $score = 0;

        foreach ($context['tokens'] as $token) {
            if (str_contains($normalized, $token)) {
                $score += 8;
            }
        }

        foreach (['manufacturer' => 34, 'model' => 42, 'name' => 10, 'type' => 6] as $field => $weight) {
            $value = trim((string) ($context[$field] ?? ''));

            if ($value !== '' && str_contains($normalized, $value)) {
                $score += $weight;
            }
        }

        if (str_contains($normalized, Str::lower($query))) {
            $score += 18;
        }

        if (
            $context['manufacturer'] !== ''
            && $context['model'] !== ''
            && str_contains($normalized, $context['manufacturer'])
            && str_contains($normalized, $context['model'])
        ) {
            $score += 50;
        }

        if (str_contains($normalized, 'logo') || str_contains($normalized, 'icon')) {
            $score -= 20;
        }

        return $score;
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
