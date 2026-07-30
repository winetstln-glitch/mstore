<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'router_id')) {
                $table->foreignId('router_id')->nullable()->after('batch_id')
                    ->constrained('routers')->nullOnDelete();
            }
            if (! Schema::hasColumn('vouchers', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('router_id')
                    ->constrained('invoices')->nullOnDelete();
            }
            if (! Schema::hasColumn('vouchers', 'customer_name')) {
                $table->string('customer_name', 120)->nullable()->after('invoice_id');
            }
            if (! Schema::hasColumn('vouchers', 'customer_phone')) {
                $table->string('customer_phone', 32)->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('vouchers', 'sold_at')) {
                $table->timestamp('sold_at')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('vouchers', 'synced_to_router')) {
                $table->boolean('synced_to_router')->default(false)->after('sold_at');
            }
            if (! Schema::hasColumn('vouchers', 'sync_error')) {
                $table->text('sync_error')->nullable()->after('synced_to_router');
            }
            if (! Schema::hasColumn('vouchers', 'hotspot_profile_id')) {
                $table->foreignId('hotspot_profile_id')->nullable()->after('batch_id')
                    ->constrained('hotspot_profiles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $cols = ['customer_name', 'customer_phone', 'sold_at', 'synced_to_router', 'sync_error'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('vouchers', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (['router_id', 'invoice_id', 'hotspot_profile_id'] as $fcol) {
                if (Schema::hasColumn('vouchers', $fcol)) {
                    try {
                        $table->dropConstrainedForeignId($fcol);
                    } catch (\Throwable $e) {
                        if (Schema::hasColumn('vouchers', $fcol)) {
                            $table->dropColumn($fcol);
                        }
                    }
                }
            }
        });
    }
};
