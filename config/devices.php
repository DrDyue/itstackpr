<?php

return [
    // Disks, kur tiek glabāti ierīču attēli. Noklusēti `public`, bet production vidē var pārlikt caur .env.
    'asset_disk' => env('DEVICE_ASSET_DISK', 'public'),
    // Oriģinālie attēli un thumbnail tiek glabāti atsevišķās mapēs, lai frontend tabulās var izmantot vieglāku failu.
    'device_image_dir' => 'devices/images',
    'thumbnail_dir' => 'devices/thumbs',
    // Lielie attēli pirms saglabāšanas tiek samazināti, lai krātuve un lapas ielāde neciestu no pārāk lieliem failiem.
    'max_dimension' => 1800,
    'thumbnail_dimension' => 480,
    // Kvalitātes vērtības ļauj balansēt starp faila izmēru un pietiekami labu vizuālo kvalitāti.
    'jpeg_quality' => 82,
    'webp_quality' => 80,
    // Upload limits kilobaitos; forma to rāda lietotājam, bet backend to izmanto validācijā.
    'max_upload_kb' => 5120,
    // Lietotāja aģents attālai attēlu priekšskatīšanai, lai ārēji serveri redzētu saprotamu pieprasījuma avotu.
    'auto_image_user_agent' => env('DEVICE_AUTO_IMAGE_USER_AGENT', 'ITStackPR Device Image Fetcher/1.0'),
];
