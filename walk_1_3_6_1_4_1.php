<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;

echo "=== Walking 1.3.6.1.4.1 ===" . date('Y-m-d H:i:s') . "\n";

$olt = Olt::find(1);
if (!$olt) {
    die("OLT 1 not found!\n");
}

$host = $olt->host . ':161';
$community = $olt->snmp_community;

echo "Host: {$host}\n";
echo "Community: {$community}\n\n";

$baseOid = '1.3.6.1.4.1';

echo "Walking {$baseOid} (this might take a while)...\n";
$raw = @snmprealwalk($host, $community, $baseOid, 60 * 1000000, 1);

if ($raw === false || !is_array($raw)) {
    die("snmprealwalk failed!\n");
}

echo "Found " . count($raw) . " entries!\n";
$outputFile = __DIR__ . '/cdata_full_1_3_6_1_4_1_dump.txt';
file_put_contents($outputFile, '');

foreach ($raw as $oid => $val) {
    $oid = ltrim($oid, '.');
    $oid = preg_replace('/^iso\./', '1.', $oid);
    file_put_contents($outputFile, "{$oid} => {$val}\n", FILE_APPEND);
}

echo "Saved to {$outputFile}\n";
echo "Done!\n";
