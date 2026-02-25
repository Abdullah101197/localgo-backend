<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Test SOUNDEX values
$term1 = 'Panadol';
$term2 = 'pandol';

$results = Illuminate\Support\Facades\DB::select("SELECT SOUNDEX(?) as s1, SOUNDEX(?) as s2", [$term1, $term2]);

echo "Term 1: $term1 -> " . $results[0]->s1 . "\n";
echo "Term 2: $term2 -> " . $results[0]->s2 . "\n";

if ($results[0]->s1 == $results[0]->s2) {
    echo "MATCH! SOUNDEX will work.\n";
} else {
    echo "NO MATCH. Need another strategy.\n";
}
