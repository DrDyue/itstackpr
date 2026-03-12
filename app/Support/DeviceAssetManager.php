<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeviceAssetManager
{
    public function disk(): string
    {
        return (string) config('devices.asset_disk', 'public');
    }

    public function storeDeviceImage(UploadedFile $file, ?string $previousPath = null): string
    {
        return $this->storeImage($file, (string) config('devices.device_image_dir', 'd'), $previousPath);
    }

    public function storeWarrantyImage(UploadedFile $file, ?string $previousPath = null): string
    {
        return $this->storeImage($file, (string) config('devices.warranty_image_dir', 'w'), $previousPath);
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk($this->disk())->url($path);
    }

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

    private function storeImage(UploadedFile $file, string $directory, ?string $previousPath = null): string
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

        if ($previousPath && $previousPath !== $path) {
            $this->delete($previousPath);
        }

        return $path;
    }

    private function optimize(UploadedFile $file): array
    {
        if (! extension_loaded('gd')) {
            return [];
        }

        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            return [];
        }

        [$width, $height, $imageType] = $imageInfo;

        $source = match ($imageType) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($file->getPathname()) : false,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($file->getPathname()) : false,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getPathname()) : false,
            default => false,
        };

        if (! $source) {
            return [];
        }

        $maxDimension = (int) config('devices.max_dimension', 1800);
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
}
