<?php

namespace App\Console\Commands;

use App\Actions\Attendance\MarkAbsentAsAlphaAction;
use App\Models\Role;
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
        $eligibleRoles = [
            Role::ADMIN,
            Role::LEADER,
            Role::FINANCE,
            Role::HRD_MANAGER,
            Role::NOC,
            Role::TECHNICIAN,
            Role::KASIR_ATK,
            Role::KASIR_WASH,
            Role::KARYAWAN_WASH,
        ];
        $excludedRoles = [Role::CUSTOMER, Role::DIREKTUR, Role::COORDINATOR, 'owner', 'owner pendiri', 'owner-pendiri'];

        // Query semua user aktif beserta role
        User::where('is_active', true)
            ->with('role')
            ->lazy()
            ->each(function ($user) use ($markAbsentAsAlphaAction, $today, &$markedCount, $eligibleRoles, $excludedRoles) {
                $this->info("Checking user: {$user->name} (Role: " . (optional($user->role)->name ?? 'N/A') . ")");

                if ($user->hasAnyRole($excludedRoles)) {
                    $this->info("→ Skipping (excluded role)");
                    return;
                }

                if (! $user->hasAnyRole($eligibleRoles)) {
                    $this->info("→ Skipping (not eligible role)");
                    return;
                }

                $this->info("→ Processing...");
                $attendance = $markAbsentAsAlphaAction->execute($user, $today);
                
                if ($attendance) {
                    $markedCount++;
                    $this->info("✓ Marked {$user->name} (" . (optional($user->role)->name ?? 'N/A') . ") as alpha for today!");
                } else {
                    $this->info("→ No action taken for {$user->name}");
                }
            });

        $this->info("Process completed! Marked {$markedCount} users as alpha.");
        return 0;
    }
}
