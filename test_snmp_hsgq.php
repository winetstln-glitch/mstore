<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;

echo "=== TEST SNMP HSGQ ===\n";
echo str_repeat("=", 80) . "\n";

$olts = Olt::where('brand', 'hsgq')->get();
if ($olts->isEmpty()) {
    die("Tidak ada OLT dengan brand hsgq!\n");
}

foreach ($olts as $olt) {
    echo "\nOLT ID: {$olt->id}\n";
    echo "Nama: {$olt->name}\n";
    echo "Host: {$olt->host}\n";
    echo "Community: " . ($olt->snmp_community ?? 'public') . "\n";
    
    $community = $olt->snmp_community ?? 'public';
    $host = $olt->host;
    
    // OID yang benar dari hasil_smnp_oid.txt
    $oidName = '1.3.6.1.4.1.50224.3.2.4.1.2';
    
    echo "\nTesting snmp2_walk OID Name: {$oidName}\n";
    echo str_repeat("-", 80) . "\n";
    
    try {
        $result = @snmp2_walk($host, $community, $oidName, 1000000, 1);
        if ($result === false) {
            echo "snmp2_walk mengembalikan FALSE!\n";
            echo "Coba dengan snmp2_real_walk:\n";
            $resultReal = @snmp2_real_walk($host, $community, $oidName, 1000000, 1);
            if ($resultReal === false) {
                echo "snmp2_real_walk juga FALSE!\n";
            } else {
                echo "snmp2_real_walk berhasil! Count: " . count($resultReal) . "\n";
                print_r($resultReal);
            }
        } else {
            echo "snmp2_walk berhasil! Count: " . count($result) . "\n";
            print_r($result);
        }
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    echo str_repeat("=", 80) . "\n";
}
