<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('genie_device_statuses')) {
            Schema::table('genie_device_statuses', function (Blueprint $table) {
                if (! Schema::hasColumn('genie_device_statuses', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('id')->constrained('customers')->nullOnDelete();
                    $table->unique('customer_id');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'onu_serial')) {
                    $table->string('onu_serial')->nullable()->after('customer_id');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'is_online')) {
                    $table->boolean('is_online')->default(false)->after('onu_serial');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'last_inform')) {
                    $table->timestamp('last_inform')->nullable()->after('is_online');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'tr069_ip')) {
                    $table->string('tr069_ip', 100)->nullable()->after('last_inform');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'connection_request_url')) {
                    $table->text('connection_request_url')->nullable()->after('tr069_ip');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'last_reason')) {
                    $table->text('last_reason')->nullable()->after('connection_request_url');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'last_notified_down_at')) {
                    $table->timestamp('last_notified_down_at')->nullable()->after('last_reason');
                }
                if (! Schema::hasColumn('genie_device_statuses', 'last_notified_up_at')) {
                    $table->timestamp('last_notified_up_at')->nullable()->after('last_notified_down_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('genie_device_statuses')) {
            Schema::table('genie_device_statuses', function (Blueprint $table) {
                if (Schema::hasColumn('genie_device_statuses', 'last_notified_up_at')) {
                    $table->dropColumn('last_notified_up_at');
                }
                if (Schema::hasColumn('genie_device_statuses', 'last_notified_down_at')) {
                    $table->dropColumn('last_notified_down_at');
                }
                if (Schema::hasColumn('genie_device_statuses', 'last_reason')) {
                    $table->dropColumn('last_reason');
                }
                if (Schema::hasColumn('genie_device_statuses', 'connection_request_url')) {
                    $table->dropColumn('connection_request_url');
                }
                if (Schema::hasColumn('genie_device_statuses', 'tr069_ip')) {
                    $table->dropColumn('tr069_ip');
                }
                if (Schema::hasColumn('genie_device_statuses', 'last_inform')) {
                    $table->dropColumn('last_inform');
                }
                if (Schema::hasColumn('genie_device_statuses', 'is_online')) {
                    $table->dropColumn('is_online');
                }
                if (Schema::hasColumn('genie_device_statuses', 'onu_serial')) {
                    $table->dropColumn('onu_serial');
                }
                if (Schema::hasColumn('genie_device_statuses', 'customer_id')) {
                    $table->dropUnique(['customer_id']);
                    $table->dropConstrainedForeignId('customer_id');
                }
            });
        }
    }
};
