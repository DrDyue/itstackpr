<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Ierīcu skaits: " . \App\Models\Device::count() . "\n";
echo "Ēku skaits: " . \App\Models\Building::count() . "\n";
echo "Ierīču tipu skaits: " . \App\Models\DeviceType::count() . "\n";
echo "Reparāciju skaits: " . \App\Models\Repair::count() . "\n";
