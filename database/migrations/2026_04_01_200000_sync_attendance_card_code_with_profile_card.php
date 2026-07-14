<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'attendance_card_code')) {
            return;
        }

        User::query()->orderBy('id')->each(function (User $user) {
            $currentCode = trim((string) ($user->attendance_card_code ?? ''));
            $username = trim((string) ($user->username ?? ''));
            $isLegacyAuto = $currentCode !== '' && strtolower($currentCode) === strtolower($username);
            if ($currentCode === '' || $isLegacyAuto) {
                $base = User::defaultAttendanceCardCodeById((int) $user->id);
                $user->update([
                    'attendance_card_code' => User::generateUniqueAttendanceCardCode($base, (int) $user->id),
                ]);
            }
        });
    }

    public function down(): void {}
};
