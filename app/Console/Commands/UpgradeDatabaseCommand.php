<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpgradeDatabaseCommand extends Command
{
    protected $signature = 'db:upgrade';
    protected $description = 'Perbarui database dari versi lama ke versi baru';

    public function handle()
    {
        $this->info('=== Memulai proses upgrade database ===');
        $this->newLine();

        $this->updateTables();
        
        $this->newLine();
        $this->info('=== Upgrade database selesai! ===');
    }

    private function updateTables()
    {
        $this->info('Step 1: Memperbarui struktur tabel yang ada...');

        $tablesToCheck = [
            'users', 'customers', 'tickets', 'installations',
            'routers', 'olts', 'onus', 'odps', 'odcs', 'closures',
            'inventory_items', 'inventory_transactions', 'assets',
            'technician_attendances', 'leave_requests', 'technician_schedules',
            'wash_services', 'wash_transactions', 'wash_transaction_items',
            'vouchers', 'packages', 'bandwidths'
        ];

        foreach ($tablesToCheck as $table) {
            if (Schema::hasTable($table)) {
                $this->line("  - Memeriksa tabel: {$table}");
                $this->updateTableColumns($table);
            }
        }

        $this->newLine();
    }

    private function updateTableColumns($table)
    {
        $columnChecks = $this->getColumnChecks();
        
        if (isset($columnChecks[$table])) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columnChecks, $table) {
                foreach ($columnChecks[$table] as $column => $definition) {
                    if (!Schema::hasColumn($table, $column)) {
                        $this->line("    - Menambahkan kolom: {$column}");
                        $definition($tableBlueprint);
                    }
                }
            });
        }
    }

    private function getColumnChecks()
    {
        return [
            'users' => [
                'daily_salary' => fn(Blueprint $t) => $t->decimal('daily_salary', 15, 2)->nullable(),
                'telegram_chat_id' => fn(Blueprint $t) => $t->string('telegram_chat_id')->nullable(),
                'avatar' => fn(Blueprint $t) => $t->string('avatar')->nullable(),
                'username' => fn(Blueprint $t) => $t->string('username')->nullable()->unique(),
                'attendance_card_code' => fn(Blueprint $t) => $t->string('attendance_card_code')->nullable()->unique(),
            ],
            'customers' => [
                'pppoe_profile' => fn(Blueprint $t) => $t->string('pppoe_profile')->nullable(),
                'pppoe_ip_local' => fn(Blueprint $t) => $t->string('pppoe_ip_local')->nullable(),
                'pppoe_ip_remote' => fn(Blueprint $t) => $t->string('pppoe_ip_remote')->nullable(),
                'billing_cycle_date' => fn(Blueprint $t) => $t->integer('billing_cycle_date')->default(1),
                'latitude' => fn(Blueprint $t) => $t->decimal('latitude', 10, 8)->nullable(),
                'longitude' => fn(Blueprint $t) => $t->decimal('longitude', 11, 8)->nullable(),
                'identity_number' => fn(Blueprint $t) => $t->string('identity_number')->nullable(),
                'auto_isolate' => fn(Blueprint $t) => $t->boolean('auto_isolate')->default(true),
                'wan_mac' => fn(Blueprint $t) => $t->string('wan_mac')->nullable(),
                'genieacs_device_id' => fn(Blueprint $t) => $t->string('genieacs_device_id')->nullable(),
                'user_id' => fn(Blueprint $t) => $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(),
            ],
            'tickets' => [
                'subject' => fn(Blueprint $t) => $t->string('subject')->nullable(),
                'photo_proof' => fn(Blueprint $t) => $t->string('photo_proof')->nullable(),
                'photo_before' => fn(Blueprint $t) => $t->string('photo_before')->nullable(),
                'odp_id' => fn(Blueprint $t) => $t->foreignId('odp_id')->nullable()->constrained()->nullOnDelete(),
                'coordinator_id' => fn(Blueprint $t) => $t->foreignId('coordinator_id')->nullable()->constrained()->nullOnDelete(),
            ],
            'routers' => [
                'location' => fn(Blueprint $t) => $t->text('location')->nullable(),
                'latitude' => fn(Blueprint $t) => $t->decimal('latitude', 10, 8)->nullable(),
                'longitude' => fn(Blueprint $t) => $t->decimal('longitude', 11, 8)->nullable(),
                'radius_secret' => fn(Blueprint $t) => $t->string('radius_secret')->nullable(),
                'region_id' => fn(Blueprint $t) => $t->foreignId('region_id')->nullable()->constrained()->nullOnDelete(),
                'vpn_enabled' => fn(Blueprint $t) => $t->boolean('vpn_enabled')->default(false),
                'vpn_server_id' => fn(Blueprint $t) => $t->foreignId('vpn_server_id')->nullable()->constrained()->nullOnDelete(),
            ],
            'olts' => [
                'is_active' => fn(Blueprint $t) => $t->boolean('is_active')->default(true),
                'snmp_version' => fn(Blueprint $t) => $t->string('snmp_version')->default('2c'),
                'snmp_community' => fn(Blueprint $t) => $t->string('snmp_community')->default('public'),
                'snmp_port' => fn(Blueprint $t) => $t->integer('snmp_port')->default(161),
                'vendor' => fn(Blueprint $t) => $t->string('vendor')->nullable(),
                'model' => fn(Blueprint $t) => $t->string('model')->nullable(),
                'firmware_version' => fn(Blueprint $t) => $t->string('firmware_version')->nullable(),
                'total_ports' => fn(Blueprint $t) => $t->integer('total_ports')->default(0),
                'last_polled_at' => fn(Blueprint $t) => $t->timestamp('last_polled_at')->nullable(),
            ],
            'onus' => [
                'serial_number' => fn(Blueprint $t) => $t->string('serial_number')->nullable(),
                'mac_address' => fn(Blueprint $t) => $t->string('mac_address')->nullable(),
                'vendor' => fn(Blueprint $t) => $t->string('vendor')->nullable(),
                'model' => fn(Blueprint $t) => $t->string('model')->nullable(),
                'firmware_version' => fn(Blueprint $t) => $t->string('firmware_version')->nullable(),
                'rx_power' => fn(Blueprint $t) => $t->decimal('rx_power', 8, 2)->nullable(),
                'tx_power' => fn(Blueprint $t) => $t->decimal('tx_power', 8, 2)->nullable(),
                'oper_status' => fn(Blueprint $t) => $t->string('oper_status')->default('unknown'),
                'last_active_at' => fn(Blueprint $t) => $t->timestamp('last_active_at')->nullable(),
                'olt_port_id' => fn(Blueprint $t) => $t->foreignId('olt_port_id')->nullable()->constrained('olt_ports')->nullOnDelete(),
            ],
            'odps' => [
                'region_id' => fn(Blueprint $t) => $t->foreignId('region_id')->nullable()->constrained()->nullOnDelete(),
                'color' => fn(Blueprint $t) => $t->string('color')->nullable(),
                'details' => fn(Blueprint $t) => $t->text('details')->nullable(),
                'path' => fn(Blueprint $t) => $t->text('path')->nullable(),
            ],
            'odcs' => [
                'region_id' => fn(Blueprint $t) => $t->foreignId('region_id')->nullable()->constrained()->nullOnDelete(),
                'details' => fn(Blueprint $t) => $t->text('details')->nullable(),
                'path' => fn(Blueprint $t) => $t->text('path')->nullable(),
            ],
            'inventory_items' => [
                'price' => fn(Blueprint $t) => $t->decimal('price', 15, 2)->nullable(),
                'category' => fn(Blueprint $t) => $t->string('category')->nullable(),
                'type_group' => fn(Blueprint $t) => $t->string('type_group')->nullable(),
                'cost' => fn(Blueprint $t) => $t->decimal('cost', 15, 2)->nullable(),
            ],
            'inventory_transactions' => [
                'coordinator_id' => fn(Blueprint $t) => $t->foreignId('coordinator_id')->nullable()->constrained()->nullOnDelete(),
                'location' => fn(Blueprint $t) => $t->string('location')->nullable(),
                'cost' => fn(Blueprint $t) => $t->decimal('cost', 15, 2)->nullable(),
                'total_cost' => fn(Blueprint $t) => $t->decimal('total_cost', 15, 2)->nullable(),
            ],
            'assets' => [
                'location' => fn(Blueprint $t) => $t->string('location')->nullable(),
                'status' => fn(Blueprint $t) => $t->string('status')->default('active'),
            ],
            'technician_attendances' => [
                'clock_in' => fn(Blueprint $t) => $t->timestamp('clock_in')->change(),
            ],
            'wash_services' => [
                'holiday_price' => fn(Blueprint $t) => $t->decimal('holiday_price', 15, 2)->nullable(),
                'is_promo' => fn(Blueprint $t) => $t->boolean('is_promo')->default(false),
                'promo_price' => fn(Blueprint $t) => $t->decimal('promo_price', 15, 2)->nullable(),
            ],
            'wash_transactions' => [
                'queue_number' => fn(Blueprint $t) => $t->integer('queue_number')->nullable(),
                'status' => fn(Blueprint $t) => $t->string('status')->default('pending'),
                'cash_change' => fn(Blueprint $t) => $t->decimal('cash_change', 15, 2)->nullable(),
            ],
            'wash_transaction_items' => [
                'service_name' => fn(Blueprint $t) => $t->string('service_name')->nullable(),
                'holiday_adjustment' => fn(Blueprint $t) => $t->decimal('holiday_adjustment', 15, 2)->default(0),
                'employee_id' => fn(Blueprint $t) => $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete(),
            ],
            'vouchers' => [
                'batch_id' => fn(Blueprint $t) => $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete(),
                'notes' => fn(Blueprint $t) => $t->text('notes')->nullable(),
            ],
            'packages' => [
                'package_type' => fn(Blueprint $t) => $t->string('package_type')->default('residential'),
                'devices_limit' => fn(Blueprint $t) => $t->integer('devices_limit')->nullable(),
                'is_promo_enabled' => fn(Blueprint $t) => $t->boolean('is_promo_enabled')->default(false),
                'promo_price' => fn(Blueprint $t) => $t->decimal('promo_price', 15, 2)->nullable(),
                'promo_start_date' => fn(Blueprint $t) => $t->date('promo_start_date')->nullable(),
                'promo_end_date' => fn(Blueprint $t) => $t->date('promo_end_date')->nullable(),
            ],
        ];
    }
}
