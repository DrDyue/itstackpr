<?php

namespace Tests\Unit;

use App\Support\DeviceAssetManager;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeviceAssetManagerTest extends TestCase
{
    public function test_it_stores_image_contents_and_thumbnail(): void
    {
        Storage::fake('public');

        $manager = app(DeviceAssetManager::class);
        $path = $manager->storeDeviceImageContents($this->tinyPng(), 'png');

        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists($manager->thumbnailPath($path));
    }

    private function tinyPng(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlH0b4AAAAASUVORK5CYII=',
            true
        ) ?: '';
    }
}
