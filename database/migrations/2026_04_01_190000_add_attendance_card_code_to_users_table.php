<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'attendance_card_code')) {
                $table->string('attendance_card_code')->nullable()->unique()->after('username');
            }
        });

        $users = DB::table('users')
            ->select('id', 'username', 'attendance_card_code')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            if (! empty($user->attendance_card_code)) {
                continue;
            }
            $base = trim((string) ($user->username ?: 'ID-'.$user->id));
            $candidate = $base;
            $suffix = 1;
            while (DB::table('users')->where('attendance_card_code', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = $base.'-'.$suffix;
                $suffix++;
            }
            DB::table('users')->where('id', $user->id)->update(['attendance_card_code' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'attendance_card_code')) {
                $table->dropUnique(['attendance_card_code']);
                $table->dropColumn('attendance_card_code');
            }
        });
    }
};
