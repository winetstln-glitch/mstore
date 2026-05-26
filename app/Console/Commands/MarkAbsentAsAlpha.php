<?php

namespace App\Console\Commands;

use App\Actions\Attendance\MarkAbsentAsAlphaAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentAsAlpha extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-alpha';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark users who didn\'t check in after cut-off time as alpha safely';

    /**
     * Execute the console command.
     */
    public function handle(MarkAbsentAsAlphaAction $markAbsentAsAlphaAction)
    {
        $this->info('Starting to mark absent users as alpha...');

        $today = Carbon::today();
        $markedCount = 0;

        // Daftar role standar yang DIWAJIBKAN absensi harian (Sudah dinormalisasi)
        $eligibleRoles = [
            'admin',
            'leader',
            'finance',
            'hrd manager',
            'noc',
            'technician',
            'kasir atk',
            'kasir wash',
            'operator wash',
            'staf keuangan',
            'karyawan wash',
            'administrator',
        ];

        // Daftar role yang DIKEÇUALIKAN
        $excludedRoles = ['customer', 'direktur', 'owner', 'owner pendiri', 'coordinator', 'koordinator'];

        // Query semua user aktif beserta role
        User::where('is_active', true)
            ->with('role')
            ->lazy()
            ->each(function ($user) use ($markAbsentAsAlphaAction, $today, &$markedCount, $eligibleRoles, $excludedRoles) {
            
                // Normalisasi nama role
                $userRole = strtolower(str_replace(['-', '_'], ' ', $user->role->name ?? ''));
                $this->info("Checking user: {$user->name} (Role: {$user->role->name ?? 'N/A'} -> Normalized: {$userRole})");

                // Skip jika role termasuk yang dikecualikan
                if (in_array($userRole, $excludedRoles, true)) {
                    $this->info("→ Skipping (excluded role)");
                    return;
                }

                // Skip jika role tidak termasuk eligible
                if (! in_array($userRole, $eligibleRoles, true)) {
                    $this->info("→ Skipping (not eligible role)");
                    return;
                }

                $this->info("→ Processing...");
                $attendance = $markAbsentAsAlphaAction->execute($user, $today);
                
                if ($attendance) {
                    $markedCount++;
                    $this->info("✓ Marked {$user->name} ({$user->role->name}) as alpha for today!");
                } else {
                    $this->info("→ No action taken for {$user->name}");
                }
            });

        $this->info("Process completed! Marked {$markedCount} users as alpha.");
        return 0;
    }
}