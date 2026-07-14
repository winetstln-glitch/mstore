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
        // Ensure Kas Utama exists
        \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

        Schema::table('atk_cash_movements', function (Blueprint $table) {
            // Add cash_id relation
            if (! Schema::hasColumn('atk_cash_movements', 'cash_id')) {
                $table->foreignId('cash_id')->nullable()->after('id')->constrained('cashes')->cascadeOnDelete();
            }
            
            // Add atk_transaction_id relation
            if (! Schema::hasColumn('atk_cash_movements', 'atk_transaction_id')) {
                $table->foreignId('atk_transaction_id')->nullable()->after('cash_id')->constrained('atk_transactions')->cascadeOnDelete();
            }
            
            // Add direction
            if (! Schema::hasColumn('atk_cash_movements', 'direction')) {
                $table->enum('direction', ['in', 'out'])->default('in')->after('movement_type');
            }
            
            // Add idempotency_key with unique index
            if (! Schema::hasColumn('atk_cash_movements', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->after('reference_id')->unique();
            }
            
            // Add occurred_at
            if (! Schema::hasColumn('atk_cash_movements', 'occurred_at')) {
                $table->timestamp('occurred_at')->nullable()->after('description');
            }
            
            // Add reversal fields
            if (! Schema::hasColumn('atk_cash_movements', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('occurred_at');
            }
            if (! Schema::hasColumn('atk_cash_movements', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_cash_movements', 'reversal_of_id')) {
                $table->foreignId('reversal_of_id')->nullable()->after('reversed_by')->constrained('atk_cash_movements')->nullOnDelete();
            }
            
            // Make atk_cash_register_id nullable
            if (Schema::hasColumn('atk_cash_movements', 'atk_cash_register_id')) {
                $table->foreignId('atk_cash_register_id')->nullable()->change();
            }
        });

        // Set default direction for existing records
        \App\Models\AtkCashMovement::whereNull('direction')
            ->whereIn('movement_type', ['sale', 'service', 'topup', 'ppob', 'owner_loan', 'opening', 'adjustment'])
            ->update(['direction' => 'in']);
        
        \App\Models\AtkCashMovement::whereNull('direction')
            ->whereIn('movement_type', ['expense', 'owner_repayment', 'withdrawal', 'transfer', 'closing'])
            ->update(['direction' => 'out']);

        // Set cash_id for existing records
        $cash = \App\Models\Cash::where('name', 'Kas Utama')->first();
        if ($cash) {
            \App\Models\AtkCashMovement::whereNull('cash_id')->update(['cash_id' => $cash->id]);
        }

        // Set occurred_at to created_at for existing records
        \App\Models\AtkCashMovement::whereNull('occurred_at')->update(['occurred_at' => \DB::raw('created_at')]);
        
        // Add unique index to Cash table for 'Kas Utama'
        if (! Schema::hasIndex('cashes', 'cashes_name_unique')) {
            Schema::table('cashes', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_cash_movements', function (Blueprint $table) {
            $table->dropForeign(['cash_id']);
            $table->dropForeign(['atk_transaction_id']);
            $table->dropForeign(['reversed_by']);
            $table->dropForeign(['reversal_of_id']);
            $table->dropColumn([
                'cash_id', 'atk_transaction_id', 'direction', 
                'idempotency_key', 'occurred_at', 'reversed_at', 
                'reversed_by', 'reversal_of_id'
            ]);
            
            // Revert movement_type enum
            $table->enum('movement_type', ['opening', 'sale', 'expense', 'owner_loan', 'owner_repayment', 'adjustment', 'closing'])->default('sale')->change();
        });
        
        Schema::table('cashes', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
