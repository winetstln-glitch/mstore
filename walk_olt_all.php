<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== WALK ALL OIDs FROM OLT ===" . PHP_EOL . PHP_EOL;

if (!isset($argv[1]) || !isset($argv[2])) {
    die("Penggunaan: php walk_olt_all.php <ip> <community> [port]" . PHP_EOL);
}

$ip = $argv[1];
$community = $argv[2];
$port = $argv[3] ?? 161;

snmp_set_oid_numeric_print(1);
snmp_set_quick_print(1);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$host = $ip . ':' . $port;
echo "Host: {$host}" . PHP_EOL;
echo "Community: {$community}" . PHP_EOL . PHP_EOL;

echo "=== sysDescr ===" . PHP_EOL;
$sysDescr = @snmpget($host, $community, '1.3.6.1.2.1.1.1.0', 10000000, 2);
if ($sysDescr) echo $sysDescr . PHP_EOL;

echo PHP_EOL . "=== sysObjectID ===" . PHP_EOL;
$sysObjectId = @snmpget($host, $community, '1.3.6.1.2.1.1.2.0', 10000000, 2);
if ($sysObjectId) echo $sysObjectId . PHP_EOL;

echo PHP_EOL . "=== WALKING 1.3.6.1.4.1 ===" . PHP_EOL;
$rawAll = @snmprealwalk($host, $community, '1.3.6.1.4.1', 30000000, 1);

if ($rawAll === false) {
    echo PHP_EOL . "FAILED: snmprealwalk 1.3.6.1.4.1" . PHP_EOL;
    echo "Error: " . (error_get_last()['message'] ?? 'unknown') . PHP_EOL;
} else {
    echo PHP_EOL . "TOTAL OIDs: " . count($rawAll) . PHP_EOL . PHP_EOL;
    $count = 0;
    foreach ($rawAll as $oid => $val) {
        $count++;
        echo $oid . " = " . $val . PHP_EOL;
        if ($count >= 500) {
            echo PHP_EOL . "... and " . (count($rawAll) - 500) . " more OIDs" . PHP_EOL;
            break;
        }
    }
}
