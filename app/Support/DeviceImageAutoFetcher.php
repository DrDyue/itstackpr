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
        ]);

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
        return $this->previewMany($attributes, null, 1)[0] ?? null;
    }

    public function previewMany(array $attributes, ?DeviceType $deviceType = null, int $batch = 1, int $perBatch = 3): array
    {
        $batch = max(1, $batch);
        $perBatch = max(1, min($perBatch, 6));
        $context = $this->searchContext($attributes);
        $results = [];
        [$searchStage, $stagePage] = $this->searchStage($batch);

        foreach ($this->searchQueries($attributes, $searchStage) as $query) {
            foreach ($this->findCommonsImageCandidates($query, $context) as $candidate) {
                $results[$candidate['url']] = $candidate;
            }
        }

        if ($results === []) {
            foreach ($this->searchQueries($attributes, $searchStage) as $query) {
                foreach ($this->findWikipediaImageCandidates($query, $context) as $candidate) {
                    $results[$candidate['url']] = $candidate;
                }
            }
        }

        if ($results === []) {
            return [];
        }

        uasort($results, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $offset = $stagePage * $perBatch;

        $final = array_values(array_map(
            fn (array $candidate) => $candidate['url'],
            array_slice($results, $offset, $perBatch)
        ));

        if ($final !== []) {
            return $final;
        }

        return $this->fallbackImageUrls($attributes, $batch, $perBatch);
    }

    public function storeFromUrl(string $imageUrl, ?string $previousPath = null): ?string
    {
        return $this->downloadAndStore($imageUrl, $previousPath);
    }

    private function searchQueries(array $attributes, int $searchStage = 1): array
    {
        $manufacturer = trim((string) ($attributes['manufacturer'] ?? ''));
        $model = trim((string) ($attributes['model'] ?? ''));

        $queries = match ($searchStage) {
            1 => [
                trim("\"{$manufacturer}\" \"{$model}\""),
                trim("{$manufacturer} {$model}"),
                trim("\"{$manufacturer} {$model}\""),
                trim("{$model} {$manufacturer}"),
            ],
            2 => [
                trim("\"{$manufacturer}\" {$model}"),
                trim("{$manufacturer} \"{$model}\""),
                trim("\"{$model}\" {$manufacturer}"),
                trim("{$model} \"{$manufacturer}\""),
            ],
            default => [
                trim("{$manufacturer} {$model} device"),
                trim("{$manufacturer} {$model} hardware"),
                trim("\"{$manufacturer}\" \"{$model}\" device"),
                trim("\"{$model}\" {$manufacturer} device"),
            ],
        };

        return array_values(array_unique(array_filter($queries, fn (string $query) => $query !== '')));
    }

    private function searchStage(int $batch): array
    {
        if ($batch <= 1) {
            return [1, 0];
        }

        if ($batch === 2) {
            return [2, 0];
        }

        // For batch >= 3 we stay in stage 3 and page forward 3-image chunks.
        return [3, $batch - 3];
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

                if ($this->isAllowedImageUrl($source) && ! $this->looksLikeNonPhoto($title)) {
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

                if ($this->isAllowedImageUrl($source) && ! $this->looksLikeNonPhoto($title . ' ' . $extract)) {
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

    private function searchContext(array $attributes): array
    {
        $tokens = collect([
            $attributes['manufacturer'] ?? '',
            $attributes['model'] ?? '',
        ])
            ->flatMap(fn (string $value) => preg_split('/[^[:alnum:]]+/u', Str::lower($value)) ?: [])
            ->filter(fn (?string $token) => $token !== null && $token !== '' && strlen($token) >= 2)
            ->values()
            ->all();

        return [
            'tokens' => array_values(array_unique($tokens)),
            'manufacturer' => Str::lower((string) ($attributes['manufacturer'] ?? '')),
            'model' => Str::lower((string) ($attributes['model'] ?? '')),
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

        foreach (['manufacturer' => 36, 'model' => 46] as $field => $weight) {
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

    private function isAllowedImageUrl(mixed $source): bool
    {
        if (! is_string($source) || ! Str::startsWith($source, ['http://', 'https://'])) {
            return false;
        }

        $path = (string) parse_url($source, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === '') {
            return true;
        }

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private function looksLikeNonPhoto(string $text): bool
    {
        $normalized = Str::lower($text);
        $blocked = ['logo', 'icon', 'vector', 'svg', 'wordmark', 'symbol', 'coat of arms'];

        foreach ($blocked as $token) {
            if (str_contains($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    private function fallbackImageUrls(array $attributes, int $batch, int $perBatch): array
    {
        $manufacturer = trim((string) ($attributes['manufacturer'] ?? ''));
        $model = trim((string) ($attributes['model'] ?? ''));
        $query = trim($manufacturer . ' ' . $model . ' device');

        if ($query === '') {
            return [];
        }

        $offset = max(0, $batch - 1) * $perBatch;
        $urls = [];

        for ($i = 1; $i <= $perBatch; $i++) {
            $sig = $offset + $i;
            $urls[] = 'https://source.unsplash.com/1600x900/?' . rawurlencode($query) . '&sig=' . $sig;
        }

        return $urls;
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
