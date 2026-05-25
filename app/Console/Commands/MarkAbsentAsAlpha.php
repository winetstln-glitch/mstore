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
        ];

        // OPTIMASI: Menggunakan lazy() untuk menghemat RAM Server saat data karyawan berjumlah banyak
        User::whereHas('role', function ($q) use ($eligibleRoles) {
            // Memfilter menggunakan helper hasRole atau pembersihan string nama role
            // Jika database Anda menggunakan format hrd-manager / operator-wash, 
            // query ini akan otomatis mencocokkan variasi dengan aman menggunakan WHERE IN
            $q->whereIn(\DB::raw("LOWER(REPLACE(REPLACE(name, '-', ' '), '_', ' '))"), $eligibleRoles);
        })
        ->where('is_active', true)
        ->with('role')
        ->lazy() // Diproses secara streaming sepotong-sepotong di memori RAM
        ->each(function ($user) use ($markAbsentAsAlphaAction, $today, &$markedCount) {
            
            // Jaring pengaman ekstra: pastikan pimpinan / koordinator tidak tidak sengaja kena alpha
            $userRole = strtolower(str_replace(['-', '_'], ' ', $user->role->name ?? ''));
            if (in_array($userRole, ['customer', 'direktur', 'owner', 'coordinator', 'koordinator'], true)) {
                return;
            }

            $attendance = $markAbsentAsAlphaAction->execute($user, $today);
            
            if ($attendance) {
                $markedCount++;
                $this->info("Marked user {$user->name} ({$user->role->name}) as alpha for today.");
            }
        });

        $this->info("Process completed! Marked {$markedCount} users as alpha.");
        return 0;
    }
}