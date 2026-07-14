<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'username')) {
                $table->string('username')->unique()->after('id');
            }
            if (! Schema::hasColumn('vouchers', 'password')) {
                $table->string('password')->nullable()->after('username');
            }
            if (! Schema::hasColumn('vouchers', 'profile')) {
                $table->string('profile')->nullable()->after('password');
            }
            if (! Schema::hasColumn('vouchers', 'duration_seconds')) {
                $table->unsignedBigInteger('duration_seconds')->nullable()->after('profile');
            }
            if (! Schema::hasColumn('vouchers', 'quota_mb')) {
                $table->unsignedBigInteger('quota_mb')->nullable()->after('duration_seconds');
            }
            if (! Schema::hasColumn('vouchers', 'status')) {
                $table->string('status')->default('unused')->after('quota_mb');
            }
            if (! Schema::hasColumn('vouchers', 'batch_id')) {
                $table->foreignId('batch_id')->nullable()->constrained('voucher_batches')->nullOnDelete()->after('status');
            }
            if (! Schema::hasColumn('vouchers', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('batch_id');
            }
            if (! Schema::hasColumn('vouchers', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('used_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            foreach (['username', 'password', 'profile', 'duration_seconds', 'quota_mb', 'status', 'batch_id', 'used_at', 'expires_at'] as $col) {
                if (Schema::hasColumn('vouchers', $col)) {
                    if ($col === 'batch_id') {
                        $table->dropConstrainedForeignId('batch_id');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
