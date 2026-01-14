<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Customers Table
        Schema::table('customers', function (Blueprint $table) {
            // router_id, pppoe_user, pppoe_password, onu_serial already exist
            
            if (!Schema::hasColumn('customers', 'pppoe_profile')) {
                $table->string('pppoe_profile')->nullable();
            }
            if (!Schema::hasColumn('customers', 'pppoe_ip_local')) {
                $table->string('pppoe_ip_local')->nullable();
            }
            if (!Schema::hasColumn('customers', 'pppoe_ip_remote')) {
                $table->string('pppoe_ip_remote')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_cycle_date')) {
                $table->integer('billing_cycle_date')->default(1); // Date of month (1-28)
            }
            if (!Schema::hasColumn('customers', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('customers', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('customers', 'identity_number')) {
                $table->string('identity_number')->nullable(); // NIK/KTP
            }
            if (!Schema::hasColumn('customers', 'auto_isolate')) {
                $table->boolean('auto_isolate')->default(true);
            }
        });

        // 2. Invoices Table (Keuangan - Tagihan)
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->date('period_date'); // Month/Year of bill
                $table->date('due_date');
                $table->decimal('amount', 12, 2);
                $table->enum('status', ['unpaid', 'paid', 'cancelled', 'overdue'])->default('unpaid');
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Transactions Table (Keuangan - Kas)
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained(); // Who handled this (Cashier/Admin)
                $table->enum('type', ['income', 'expense']);
                $table->decimal('amount', 12, 2);
                $table->string('method')->default('cash'); // cash, transfer, gateway
                $table->string('reference_number')->nullable();
                $table->string('category')->nullable(); // e.g., 'Internet Payment', 'Server Maintenance'
                $table->text('description')->nullable();
                $table->date('transaction_date');
                $table->timestamps();
            });
        }

        // 4. Technician Attendance (Fitur Teknisi)
        if (!Schema::hasTable('technician_attendances')) {
            Schema::create('technician_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained(); // The technician
                $table->timestamp('clock_in');
                $table->timestamp('clock_out')->nullable();
                $table->string('photo_clock_in')->nullable();
                $table->string('photo_clock_out')->nullable();
                $table->decimal('lat_clock_in', 10, 8)->nullable();
                $table->decimal('lng_clock_in', 11, 8)->nullable();
                $table->decimal('lat_clock_out', 10, 8)->nullable();
                $table->decimal('lng_clock_out', 11, 8)->nullable();
                $table->string('status')->default('present'); // present, permit, sick
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. WhatsApp/Notification Logs (Fitur Chat/Notif)
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('target_phone');
                $table->string('type'); // whatsapp, email, sms
                $table->string('category'); // invoice, isolate, promo, gamas
                $table->text('message');
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->text('response')->nullable(); // API response
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('technician_attendances');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('invoices');
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'pppoe_profile', 'pppoe_ip_local', 'pppoe_ip_remote',
                'billing_cycle_date', 'latitude', 'longitude', 
                'identity_number', 'auto_isolate'
            ]);
        });
    }
};
