<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! $this->hasIndex('customers', 'idx_customers_status')) {
                $table->index('status');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (! $this->hasIndex('tickets', 'idx_tickets_status')) {
                $table->index('status');
            }
            if (! $this->hasIndex('tickets', 'idx_tickets_created_at')) {
                $table->index('created_at');
            }
        });

        Schema::table('installations', function (Blueprint $table) {
            if (! $this->hasIndex('installations', 'idx_installations_status')) {
                $table->index('status');
            }
            if (! $this->hasIndex('installations', 'idx_installations_plan_date')) {
                $table->index('plan_date');
            }
        });

        Schema::table('technician_attendances', function (Blueprint $table) {
            if (! $this->hasIndex('technician_attendances', 'idx_attendances_user_status')) {
                $table->index(['user_id', 'status']);
            }
        });
        
        Schema::table('genie_device_statuses', function (Blueprint $table) {
            if (! $this->hasIndex('genie_device_statuses', 'idx_genie_updated_at')) {
                $table->index('updated_at');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $conn = Schema::getConnection();
        $dbType = $conn->getDriverName();

        if ($dbType === 'sqlite') {
            $results = $conn->select("PRAGMA index_list(\"$table\")");
            foreach ($results as $row) {
                if ($row->name === $index) {
                    return true;
                }
            }

            return false;
        }

        // For MySQL/MariaDB/PostgreSQL, we can use a more generic approach or let it fail silently
        try {
            $sm = $conn->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes($table);

            return array_key_exists($index, $indexes);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });
        Schema::table('installations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['plan_date']);
        });
        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
        });
        Schema::table('genie_device_statuses', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });
    }
};
