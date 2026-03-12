<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class DeviceAssetController extends Controller
{
    public function show(string $path)
    {
        $disk = Storage::disk((string) config('devices.asset_disk', 'public'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
