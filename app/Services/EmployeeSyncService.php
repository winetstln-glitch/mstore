<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Models\WashEmployee;

class EmployeeSyncService
{
    public function syncFromUser(User $user): void
    {
        if (! $this->shouldSyncUser($user)) {
            return;
        }

        $employee = Employee::query()->firstOrNew(['user_id' => $user->id]);

        $employee->full_name = $user->name;
        $employee->phone = $user->phone ?: ($employee->phone ?: '-');
        $employee->email = $user->email ?: ($employee->email ?: 'user-'.$user->id.'@mstore.local');
        $employee->position = $user->role?->label ?: ($employee->position ?: 'Karyawan');
        $employee->department = $this->departmentFromRole($user->role?->name);

        if (! $employee->exists) {
            $employee->date_of_birth = now()->subYears(20)->format('Y-m-d');
            $employee->gender = 'Laki-laki';
            $employee->address = '-';
            $employee->nik = 'AUTO-'.$user->id;
            $employee->join_date = optional($user->created_at)->format('Y-m-d') ?: now()->format('Y-m-d');
            $employee->employment_status = 'Tetap';
        } else {
            $employee->date_of_birth = $employee->date_of_birth ?: now()->subYears(20)->format('Y-m-d');
            $employee->gender = $employee->gender ?: 'Laki-laki';
            $employee->address = $employee->address ?: '-';
            $employee->nik = $employee->nik ?: 'AUTO-'.$user->id;
            $employee->join_date = $employee->join_date ?: (optional($user->created_at)->format('Y-m-d') ?: now()->format('Y-m-d'));
            $employee->employment_status = $employee->employment_status ?: 'Tetap';
        }

        $employee->save();
    }

    public function unlinkUser(int $userId): void
    {
        Employee::query()->where('user_id', $userId)->update(['user_id' => null]);
    }

    public function syncFromWashEmployee(WashEmployee $washEmployee): void
    {
        $employee = Employee::query()
            ->where('wash_employee_id', $washEmployee->id)
            ->orWhere(function ($query) use ($washEmployee) {
                if ($washEmployee->user_id) {
                    $query->where('user_id', $washEmployee->user_id);
                }
            })
            ->first();

        if (! $employee) {
            $employee = new Employee;
            $employee->date_of_birth = now()->subYears(20)->format('Y-m-d');
            $employee->gender = 'Laki-laki';
            $employee->address = '-';
            $employee->nik = 'WASH-'.$washEmployee->id;
            $employee->join_date = now()->format('Y-m-d');
            $employee->employment_status = 'Tetap';
        }

        $employee->wash_employee_id = $washEmployee->id;
        $employee->user_id = $washEmployee->user_id ?: $employee->user_id;
        $employee->full_name = $washEmployee->name ?: $employee->full_name;
        $employee->phone = $washEmployee->phone ?: ($employee->phone ?: '-');
        $employee->email = $employee->email ?: (optional($washEmployee->user)->email ?: 'wash-'.$washEmployee->id.'@mstore.local');
        if (! $employee->position || $employee->position === 'Karyawan Wash') {
            $employee->position = 'Operator Wash';
        }
        $employee->department = 'Wash';
        $employee->save();
    }

    public function unlinkWashEmployee(int $washEmployeeId): void
    {
        Employee::query()->where('wash_employee_id', $washEmployeeId)->update(['wash_employee_id' => null]);
    }

    public function shouldSyncUser(User $user): bool
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if ($roleName === '') {
            return false;
        }

        return in_array($roleName, $this->allowedRoles(), true);
    }

    public function allowedRoles(): array
    {
        return [
            'admin',
            'noc',
            'network-operations-center',
            'technician',
            'coordinator',
            'finance',
            'kasir-atk',
            'kasir-wash',
            'karyawan-wash',
        ];
    }

    public function departmentFromRole(?string $roleName): string
    {
        return match ($roleName) {
            'technician', 'noc', 'network-operations-center' => 'Teknis',
            'finance' => 'Keuangan',
            'kasir-wash', 'karyawan-wash' => 'Wash',
            'kasir-atk' => 'ATK',
            default => 'Operasional',
        };
    }
}
