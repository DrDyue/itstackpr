<?php

return [
    'asset_disk' => env('DEVICE_ASSET_DISK', env('FILESYSTEM_DISK', 'public')),
    'device_image_dir' => 'd',
    'warranty_image_dir' => 'w',
    'max_dimension' => 1800,
    'jpeg_quality' => 82,
    'webp_quality' => 80,
    'max_upload_kb' => 5120,
];
