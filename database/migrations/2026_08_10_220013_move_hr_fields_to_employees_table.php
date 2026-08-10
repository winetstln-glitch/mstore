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
        // 1. Add columns to employees table if they don't exist
        if (!Schema::hasColumn('employees', 'annual_leave_quota')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->integer('annual_leave_quota')->default(12)->after('monthly_salary');
                $table->integer('annual_leave_used')->default(0)->after('annual_leave_quota');
                $table->integer('sick_leave_quota')->default(14)->after('annual_leave_used');
                $table->integer('sick_leave_used')->default(0)->after('sick_leave_quota');
                $table->string('bank_name')->nullable()->after('sick_leave_used');
                $table->string('bank_account_number')->nullable()->after('bank_name');
                $table->string('bank_account_name')->nullable()->after('bank_account_number');
                $table->string('attendance_card_code')->nullable()->after('bank_account_name');
                $table->string('attendance_device_hash')->nullable()->after('attendance_card_code');
                $table->timestamp('attendance_device_locked_at')->nullable()->after('attendance_device_hash');
            });
            
            // 2. Migrate data
            $users = \Illuminate\Support\Facades\DB::table('users')->get();
            foreach ($users as $user) {
                \Illuminate\Support\Facades\DB::table('employees')
                    ->where('user_id', $user->id)
                    ->update([
                        'annual_leave_quota' => $user->annual_leave_quota ?? 12,
                        'annual_leave_used' => $user->annual_leave_used ?? 0,
                        'sick_leave_quota' => $user->sick_leave_quota ?? 14,
                        'sick_leave_used' => $user->sick_leave_used ?? 0,
                        'bank_name' => $user->bank_name ?? null,
                        'bank_account_number' => $user->bank_account_number ?? null,
                        'bank_account_name' => $user->bank_account_name ?? null,
                        'attendance_card_code' => $user->attendance_card_code ?? null,
                        'attendance_device_hash' => $user->attendance_device_hash ?? null,
                        'attendance_device_locked_at' => $user->attendance_device_locked_at ?? null,
                    ]);
            }
        }

        // 3. Drop columns from users table

        $columnsToDrop = [
            'daily_salary',
            'monthly_salary',
            'annual_leave_quota',
            'annual_leave_used',
            'sick_leave_quota',
            'sick_leave_used',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'attendance_card_code',
            'attendance_device_hash',
            'attendance_device_locked_at',
        ];

        foreach ($columnsToDrop as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back to users
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('daily_salary', 15, 2)->default(0);
            $table->decimal('monthly_salary', 15, 2)->default(0);
            $table->integer('annual_leave_quota')->default(12);
            $table->integer('annual_leave_used')->default(0);
            $table->integer('sick_leave_quota')->default(14);
            $table->integer('sick_leave_used')->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('attendance_card_code')->nullable();
            $table->string('attendance_device_hash')->nullable();
            $table->timestamp('attendance_device_locked_at')->nullable();
        });

        // Drop from employees
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'annual_leave_quota',
                'annual_leave_used',
                'sick_leave_quota',
                'sick_leave_used',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'attendance_card_code',
                'attendance_device_hash',
                'attendance_device_locked_at',
            ]);
        });
    }
};
