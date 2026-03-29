<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Atbild par ierīču attēlu saglabāšanu, optimizēšanu un atdošanu.
 */
class DeviceAssetManager
{
    /**
     * Atgriež aktīvo failu disku no konfigurācijas.
     */
    public function disk(): string
    {
        return (string) config('devices.asset_disk', 'public');
    }

    /**
     * Saglabā augšupielādētu ierīces attēlu.
     */
    public function storeDeviceImage(UploadedFile $file, ?string $previousPath = null): string
    {
        return $this->storeImage($file, (string) config('devices.device_image_dir', 'd'), $previousPath, true);
    }

    /**
     * Saglabā attēlu no jau ielasīta satura.
     */
    public function storeDeviceImageContents(string $contents, ?string $extension = null, ?string $previousPath = null): string
    {
        return $this->storeImageContents(
            $contents,
            (string) config('devices.device_image_dir', 'd'),
            $extension,
            $previousPath,
            true
        );
    }

    /**
     * Izveido pieejamu URL gan lokāliem, gan attāliem attēliem.
     */
    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return route('device-assets.remote-preview', ['url' => $path]);
        }

        $diskName = $this->disk();
        $driver = config("filesystems.disks.{$diskName}.driver");

        if (in_array($driver, ['local'], true)) {
            return route('device-assets.show', ['path' => $path]);
        }

        return Storage::disk($diskName)->url($path);
    }

    /**
     * Dzēš attēlu no failu glabātuves.
     */
    public function delete(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $disk = Storage::disk($this->disk());

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Atgriež mazā priekšskatījuma URL.
     */
    public function thumbUrl(?string $path): ?string
    {
        if ($path && Str::startsWith($path, ['http://', 'https://'])) {
            return route('device-assets.remote-preview', ['url' => $path]);
        }

        return $this->url($this->thumbnailPath($path));
    }

    /**
     * Aprēķina thumbnails faila ceļu.
     */
    public function thumbnailPath(?string $path): ?string
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return null;
        }

        return trim((string) config('devices.thumbnail_dir', 'devices/thumbs'), '/') . '/' . basename($path);
    }

    private function storeImage(UploadedFile $file, string $directory, ?string $previousPath = null, bool $withThumbnail = false): string
    {
        $optimized = $this->optimize($file);

        $extension = $optimized['extension'] ?? strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $path = trim($directory, '/') . '/' . Str::lower(Str::random(40)) . '.' . $extension;
        $disk = Storage::disk($this->disk());

        if (isset($optimized['contents'])) {
            $disk->put($path, $optimized['contents'], ['visibility' => 'public']);
        } else {
            $disk->putFileAs(trim($directory, '/'), $file, basename($path), ['visibility' => 'public']);
        }

        if ($withThumbnail) {
            $thumbnail = $this->optimize($file, (int) config('devices.thumbnail_dimension', 480));
            if (isset($thumbnail['contents'])) {
                $disk->put($this->thumbnailPath($path), $thumbnail['contents'], ['visibility' => 'public']);
            }
        }

        if ($previousPath && $previousPath !== $path) {
            $this->delete($previousPath);
            $this->delete($this->thumbnailPath($previousPath));
        }

        return $path;
    }

    private function storeImageContents(
        string $contents,
        string $directory,
        ?string $extension = null,
        ?string $previousPath = null,
        bool $withThumbnail = false
    ): string {
        $optimized = $this->optimizeContents($contents);
        $extension = $optimized['extension']
            ?? $this->normalizeExtension($extension)
            ?? 'jpg';
        $path = trim($directory, '/') . '/' . Str::lower(Str::random(40)) . '.' . $extension;
        $disk = Storage::disk($this->disk());

        $disk->put($path, $optimized['contents'] ?? $contents, ['visibility' => 'public']);

        if ($withThumbnail) {
            $thumbnail = $this->optimizeContents($contents, (int) config('devices.thumbnail_dimension', 480));
            $disk->put(
                $this->thumbnailPath($path),
                $thumbnail['contents'] ?? ($optimized['contents'] ?? $contents),
                ['visibility' => 'public']
            );
        }

        if ($previousPath && $previousPath !== $path) {
            $this->delete($previousPath);
            $this->delete($this->thumbnailPath($previousPath));
        }

        return $path;
    }

    private function optimize(UploadedFile $file, ?int $maxDimension = null): array
    {
        $contents = @file_get_contents($file->getPathname());

        if ($contents === false) {
            return [];
        }

        return $this->optimizeContents($contents, $maxDimension);
    }

    private function optimizeContents(string $contents, ?int $maxDimension = null): array
    {
        if (! extension_loaded('gd') || $contents === '') {
            return [];
        }

        $imageInfo = @getimagesizefromstring($contents);
        if ($imageInfo === false) {
            return [];
        }

        [$width, $height, $imageType] = $imageInfo;

        $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
        if (! in_array($imageType, $supportedTypes, true) || ! function_exists('imagecreatefromstring')) {
            return [];
        }

        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return [];
        }

        $maxDimension = $maxDimension ?: (int) config('devices.max_dimension', 1800);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $canvas) {
            imagedestroy($source);
            return [];
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        $encoded = false;
        $extension = 'jpg';

        if (function_exists('imagewebp')) {
            $encoded = imagewebp($canvas, null, (int) config('devices.webp_quality', 80));
            $extension = 'webp';
        }

        if (! $encoded) {
            $encoded = imagejpeg($canvas, null, (int) config('devices.jpeg_quality', 82));
            $extension = 'jpg';
        }

        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (! $encoded || $contents === false) {
            return [];
        }

        return [
            'contents' => $contents,
            'extension' => $extension,
        ];
    }

    private function normalizeExtension(?string $extension): ?string
    {
        $extension = strtolower(trim((string) $extension));

        return match ($extension) {
            'jpeg' => 'jpg',
            'jpg', 'png', 'webp', 'gif' => $extension,
            default => null,
        };
    }
}
