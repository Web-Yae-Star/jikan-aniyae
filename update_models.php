<?php
$base = 'https://raw.githubusercontent.com/mongodb/mongo-php-library/1.16.0/src/Model';
$models = ['BSONArray.php', 'BSONDocument.php'];
$baseDir = __DIR__;
$vendorDir = $baseDir . '/vendor/mongodb/mongodb/src/Model';

if (!is_dir($vendorDir)) {
    echo "Vendor path not found: $vendorDir\n";
    exit(1);
}

foreach ($models as $model) {
    $url = "$base/$model";
    $path = $vendorDir . '/' . $model;
    $content = file_get_contents($url);
    if ($content === false) {
        echo "Failed to download $url\n";
        continue;
    }
    file_put_contents($path, $content);
    echo "Updated: $path\n";
}
