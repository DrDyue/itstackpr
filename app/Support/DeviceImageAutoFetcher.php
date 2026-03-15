<?php

namespace App\Support;

use App\Models\Device;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DeviceImageAutoFetcher
{
    private const SOURCE_BONUS = [
        'openverse' => 30,
        'commons' => 18,
        'wikipedia' => 10,
        'fallback' => -120,
    ];

    public function __construct(private readonly DeviceAssetManager $assetManager)
    {
    }

    public function populate(Device $device): bool
    {
        if (! config('devices.auto_image_enabled', true) || filled($device->device_image_url)) {
            return false;
        }

        $candidate = $this->searchCandidates([
            'manufacturer' => $device->manufacturer,
            'model' => $device->model,
        ], 1, 1)[0] ?? null;

        if (! $candidate || empty($candidate['image_url'])) {
            return false;
        }

        // Save the remote image URL directly so automatic fill does not depend on server-side downloads.
        $device->forceFill(['device_image_url' => $candidate['image_url']])->save();

        return true;
    }

    public function preview(array $attributes): ?string
    {
        return $this->previewMany($attributes, 1, 1)[0] ?? null;
    }

    public function previewMany(array $attributes, int $batch = 1, int $perBatch = 3): array
    {
        return array_values(array_map(
            fn (array $candidate) => (string) $candidate['image_url'],
            $this->searchCandidates($attributes, $batch, $perBatch)
        ));
    }

    public function searchCandidates(array $attributes, int $batch = 1, int $perBatch = 3): array
    {
        $batch = max(1, $batch);
        $perBatch = max(1, min($perBatch, 6));

        $search = $this->buildSearchContext($attributes);
        if ($search['query'] === '') {
            return [];
        }

        $offset = ($batch - 1) * $perBatch;
        $needed = $offset + $perBatch;
        $pageSize = min(18, max(9, $needed * 2));

        $candidates = collect();

        foreach ($search['queries'] as $query) {
            foreach ($this->openverseCandidates($query, 1, $pageSize) as $candidate) {
                $candidates->push($candidate);
            }

            foreach ($this->commonsCandidates($query, 1, $pageSize) as $candidate) {
                $candidates->push($candidate);
            }

            foreach ($this->wikipediaCandidates($query, 1, $pageSize) as $candidate) {
                $candidates->push($candidate);
            }

            if ($candidates->count() >= ($needed * 4)) {
                break;
            }
        }

        $ranked = $candidates
            ->filter(fn (array $candidate) => $this->isAllowedImageUrl($candidate['preview_url'] ?? null))
            ->map(function (array $candidate) use ($search) {
                $candidate['score'] = $this->scoreCandidate($candidate, $search['manufacturer'], $search['model']);

                return $candidate;
            })
            ->filter(fn (array $candidate) => ($candidate['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->unique(fn (array $candidate) => $candidate['image_url'] ?? $candidate['preview_url'])
            ->values();

        $combined = $ranked
            ->merge($this->fallbackCandidates($search['query'], 1, max($needed, $perBatch * 3)))
            ->unique(fn (array $candidate) => $candidate['image_url'])
            ->values()
            ->slice($offset, $perBatch)
            ->all();

        return $combined;
    }

    public function storeFromUrl(string $imageUrl, ?string $previousPath = null): ?string
    {
        return $this->downloadAndStore($imageUrl, $previousPath);
    }

    private function buildSearchContext(array $attributes): array
    {
        $manufacturer = $this->normalizeQueryPart((string) ($attributes['manufacturer'] ?? ''));
        $model = $this->normalizeQueryPart((string) ($attributes['model'] ?? ''));
        $query = trim($manufacturer . ' ' . $model);

        $queries = collect([
            $query,
            trim($query . ' photo'),
            trim($query . ' product'),
            trim($query . ' hardware'),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'manufacturer' => $manufacturer,
            'model' => $model,
            'query' => $query,
            'queries' => $queries,
        ];
    }

    private function openverseCandidates(string $query, int $batch, int $pageSize): array
    {
        try {
            $response = Http::timeout((int) config('devices.auto_image_timeout', 4))
                ->retry(1, 200)
                ->acceptJson()
                ->withUserAgent((string) config('devices.auto_image_user_agent', 'ITStackPR Device Image Fetcher/1.0'))
                ->get('https://api.openverse.org/v1/images/', [
                    'q' => $query,
                    'page' => $batch,
                    'page_size' => $pageSize,
                    'mature' => 'false',
                ]);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('results', []))
                ->map(function (array $item) {
                    $previewUrl = (string) ($item['thumbnail'] ?? $item['url'] ?? '');
                    $imageUrl = (string) ($item['url'] ?? $previewUrl);

                    return [
                        'preview_url' => $previewUrl,
                        'image_url' => $imageUrl,
                        'source' => 'openverse',
                        'label' => (string) ($item['title'] ?? 'Openverse'),
                    ];
                })
                ->filter(fn (array $candidate) => $this->isAllowedImageUrl($candidate['preview_url']) && ! $this->looksLikeNonPhoto($candidate['label']))
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function commonsCandidates(string $query, int $batch, int $pageSize): array
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
                    'gsrlimit' => $pageSize,
                    'gsroffset' => ($batch - 1) * $pageSize,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url',
                    'iiurlwidth' => 1200,
                ]);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('query.pages', []))
                ->sortBy(fn (array $page) => $page['index'] ?? PHP_INT_MAX)
                ->map(function (array $page) {
                    $previewUrl = (string) (data_get($page, 'imageinfo.0.thumburl') ?: data_get($page, 'imageinfo.0.url') ?: '');
                    $imageUrl = (string) (data_get($page, 'imageinfo.0.url') ?: $previewUrl);
                    $label = (string) ($page['title'] ?? 'Wikimedia Commons');

                    return [
                        'preview_url' => $previewUrl,
                        'image_url' => $imageUrl,
                        'source' => 'commons',
                        'label' => $label,
                    ];
                })
                ->filter(fn (array $candidate) => $this->isAllowedImageUrl($candidate['preview_url']) && ! $this->looksLikeNonPhoto($candidate['label']))
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function wikipediaCandidates(string $query, int $batch, int $pageSize): array
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
                    'gsrlimit' => $pageSize,
                    'gsroffset' => ($batch - 1) * $pageSize,
                    'prop' => 'pageimages|extracts',
                    'piprop' => 'original|thumbnail',
                    'pithumbsize' => 1200,
                    'exintro' => 1,
                    'explaintext' => 1,
                ]);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('query.pages', []))
                ->sortBy(fn (array $page) => $page['index'] ?? PHP_INT_MAX)
                ->map(function (array $page) {
                    $previewUrl = (string) (data_get($page, 'thumbnail.source') ?: data_get($page, 'original.source') ?: '');
                    $imageUrl = (string) (data_get($page, 'original.source') ?: $previewUrl);
                    $label = trim(((string) ($page['title'] ?? '')) . ' ' . ((string) ($page['extract'] ?? '')));

                    return [
                        'preview_url' => $previewUrl,
                        'image_url' => $imageUrl,
                        'source' => 'wikipedia',
                        'label' => $label !== '' ? $label : 'Wikipedia',
                    ];
                })
                ->filter(fn (array $candidate) => $this->isAllowedImageUrl($candidate['preview_url']) && ! $this->looksLikeNonPhoto($candidate['label']))
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function fallbackCandidates(string $query, int $start, int $count): Collection
    {
        return collect(range($start, $start + $count - 1))
            ->map(function (int $sig) use ($query) {
                $url = 'https://source.unsplash.com/1600x900/?' . rawurlencode($query . ' device') . '&sig=' . $sig;

                return [
                    'preview_url' => $url,
                    'image_url' => $url,
                    'source' => 'fallback',
                    'label' => 'Fallback',
                ];
            });
    }

    private function isAllowedImageUrl(mixed $url): bool
    {
        if (! is_string($url) || ! Str::startsWith($url, ['http://', 'https://'])) {
            return false;
        }

        $normalized = Str::lower($url);
        foreach (['.svg', '.tif', '.tiff', '.pdf', '.djvu'] as $blockedExtension) {
            if (str_contains($normalized, $blockedExtension)) {
                return false;
            }
        }

        return true;
    }

    private function looksLikeNonPhoto(string $text): bool
    {
        $normalized = Str::lower($text);
        $blocked = ['logo', 'icon', 'vector', 'wordmark', 'symbol', 'coat of arms'];

        foreach ($blocked as $token) {
            if (str_contains($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    private function scoreCandidate(array $candidate, string $manufacturer, string $model): int
    {
        $text = Str::lower(trim(implode(' ', [
            (string) ($candidate['label'] ?? ''),
            (string) ($candidate['image_url'] ?? ''),
            (string) ($candidate['preview_url'] ?? ''),
        ])));

        if ($text === '' || $this->looksLikeNonPhoto($text)) {
            return 0;
        }

        $score = self::SOURCE_BONUS[$candidate['source'] ?? 'fallback'] ?? 0;
        $manufacturerTokens = $this->tokenize($manufacturer);
        $modelTokens = $this->tokenize($model);

        if ($manufacturer !== '' && str_contains($text, Str::lower($manufacturer))) {
            $score += 80;
        }

        if ($model !== '' && str_contains($text, Str::lower($model))) {
            $score += 140;
        }

        foreach ($manufacturerTokens as $token) {
            if (strlen($token) >= 2 && str_contains($text, $token)) {
                $score += 20;
            }
        }

        foreach ($modelTokens as $token) {
            if (strlen($token) >= 2 && str_contains($text, $token)) {
                $score += ctype_digit($token) ? 18 : 30;
            }
        }

        if ($manufacturer !== '' && $model !== '' && str_contains($text, Str::lower($manufacturer)) && str_contains($text, Str::lower($model))) {
            $score += 120;
        }

        foreach (['switch', 'router', 'laptop', 'desktop', 'monitor', 'printer', 'server', 'phone', 'tablet', 'device'] as $photoToken) {
            if (str_contains($text, $photoToken)) {
                $score += 6;
            }
        }

        return $score;
    }

    private function normalizeQueryPart(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function tokenize(string $value): array
    {
        return collect(preg_split('/[^a-z0-9]+/i', Str::lower($value)) ?: [])
            ->filter(fn (string $token) => $token !== '')
            ->unique()
            ->values()
            ->all();
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
