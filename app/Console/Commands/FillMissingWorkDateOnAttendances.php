<?php

namespace App\Console\Commands;

use App\Models\TechnicianAttendance;
use Illuminate\Console\Command;

class FillMissingWorkDateOnAttendances extends Command
{
    protected $signature = 'attendance:fill-missing-work-date';
    protected $description = 'Fill work_date column for existing attendances using clock_in date';

    public function handle()
    {
        $this->info('Starting to fill missing work_date...');

        $updatedCount = 0;
        $attendances = TechnicianAttendance::whereNull('work_date')->get();

        foreach ($attendances as $attendance) {
            if ($attendance->clock_in) {
                $attendance->work_date = $attendance->clock_in->toDateString();
                $attendance->save();
                $updatedCount++;
                $this->info("Updated: ID {$attendance->id} - Work date set to {$attendance->work_date}");
            }
        }

        $this->info("Process completed! Updated {$updatedCount} attendances.");
        return 0;
    }
}
