<?php

return [
    'asset_disk' => env('DEVICE_ASSET_DISK', 'public'),
    'device_image_dir' => 'devices/images',
    'warranty_image_dir' => 'devices/warranty',
    'thumbnail_dir' => 'devices/thumbs',
    'max_dimension' => 1800,
    'thumbnail_dimension' => 480,
    'jpeg_quality' => 82,
    'webp_quality' => 80,
    'max_upload_kb' => 5120,
    'auto_image_user_agent' => env('DEVICE_AUTO_IMAGE_USER_AGENT', 'ITStackPR Device Image Fetcher/1.0'),
];
