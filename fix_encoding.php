<?php
/**
 * Fix HTML entities in all Blade files
 */

$entities = [
    '&#257;' => 'ā',
    '&#262;' => 'Č',
    '&#263;' => 'č',
    '&#270;' => 'Đ',
    '&#271;' => 'đ',
    '&#275;' => 'ē',
    '&#290;' => 'Ģ',
    '&#291;' => 'ģ',
    '&#298;' => 'Ī',
    '&#299;' => 'ī',
    '&#310;' => 'Ķ',
    '&#311;' => 'ķ',
    '&#315;' => 'Ļ',
    '&#316;' => 'ļ',
    '&#325;' => 'Ņ',
    '&#326;' => 'ņ',
    '&#352;' => 'Š',
    '&#353;' => 'š',
    '&#362;' => 'Ū',
    '&#363;' => 'ū',
    '&#381;' => 'Ž',
    '&#382;' => 'ž',
    '&#268;' => 'Č',
    '&#269;' => 'č',
];

function fixEncodingInFile($filePath) {
    global $entities;
    
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    foreach ($entities as $entity => $char) {
        $content = str_replace($entity, $char, $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✓ Fixed: " . basename($filePath) . "\n";
        return true;
    }
    
    return false;
}

// Get all blade files
$bladeFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/resources/views'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getFilename(), '.blade.php') !== false) {
        $bladeFiles[] = $file->getPathname();
    }
}

$fixed = 0;
$checked = 0;

foreach ($bladeFiles as $file) {
    $checked++;
    if (fixEncodingInFile($file)) {
        $fixed++;
    }
}

echo "\n===========================================\n";
echo "Processing complete!\n";
echo "Checked: $checked files\n";
echo "Fixed: $fixed files\n";
echo "===========================================\n";
