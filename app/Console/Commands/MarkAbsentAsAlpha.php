<?php

namespace App\Console\Commands;

use App\Actions\Attendance\MarkAbsentAsAlphaAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentAsAlpha extends Command
{
    protected $signature = 'attendance:mark-alpha';
    protected $description = 'Mark users who didn\'t check in after cut-off time as alpha';

    public function handle(MarkAbsentAsAlphaAction $markAbsentAsAlphaAction)
    {
        $this->info('Starting to mark absent users as alpha...');

        $users = User::whereHas('role', function ($q) {
            $q->where('name', '!=', 'customer');
        })->where('is_active', true)
          ->with('role')
          ->get();

        $today = Carbon::today();
        $markedCount = 0;

        foreach ($users as $user) {
            $attendance = $markAbsentAsAlphaAction->execute($user, $today);
            if ($attendance) {
                $markedCount++;
                $this->info("Marked user {$user->name} as alpha for today.");
            }
        }

        $this->info("Process completed! Marked {$markedCount} users as alpha.");
        return 0;
    }
}
