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

        $employee = $this->findEmployeeForUser($user) ?? new Employee;
        $employee->user_id = $user->id;

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
        $this->cleanupDuplicateEmployeesByUserId($user->id);
        $this->cleanupDuplicateEmployeesByName($employee->full_name);
    }

    public function unlinkUser(int $userId): void
    {
        Employee::query()->where('user_id', $userId)->update(['user_id' => null]);
    }

    public function syncFromWashEmployee(WashEmployee $washEmployee): void
    {
        $employee = $this->findEmployeeForWashEmployee($washEmployee) ?? new Employee;

        if (! $employee->exists) {
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

        // Final cleanup to ensure no duplicates for this wash_employee_id or user_id or name
        $this->cleanupDuplicateEmployeesByWashEmployeeId($washEmployee->id);
        if ($employee->user_id) {
            $this->cleanupDuplicateEmployeesByUserId($employee->user_id);
        }
        $this->cleanupDuplicateEmployeesByName($employee->full_name);
    }

    private function cleanupDuplicateEmployeesByName(string $name): void
    {
        $items = Employee::query()
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($name))])
            ->orderBy('id')
            ->get();

        if ($items->count() <= 1) {
            return;
        }

        $keeper = $items->sortByDesc(fn (Employee $employee) => $this->employeeQualityScore($employee))->first();
        foreach ($items as $item) {
            if ($item->id === $keeper->id) {
                continue;
            }

            if (! $keeper->user_id && $item->user_id) {
                $keeper->user_id = $item->user_id;
            }
            if (! $keeper->wash_employee_id && $item->wash_employee_id) {
                $keeper->wash_employee_id = $item->wash_employee_id;
            }
            if (($keeper->phone === null || $keeper->phone === '' || $keeper->phone === '-') && $item->phone && $item->phone !== '-') {
                $keeper->phone = $item->phone;
            }
            if (($keeper->address === null || trim((string) $keeper->address) === '' || trim((string) $keeper->address) === '-') && $item->address && trim((string) $item->address) !== '-') {
                $keeper->address = $item->address;
            }
            if (($keeper->nik === null || trim((string) $keeper->nik) === '' || str_starts_with((string) $keeper->nik, 'AUTO-') || str_starts_with((string) $keeper->nik, 'WASH-')) && $item->nik && ! str_starts_with((string) $item->nik, 'AUTO-') && ! str_starts_with((string) $item->nik, 'WASH-')) {
                $keeper->nik = $item->nik;
            }
            if (($keeper->email === null || trim((string) $keeper->email) === '') && $item->email) {
                $keeper->email = $item->email;
            }
            if (($keeper->position === null || trim((string) $keeper->position) === '' || trim((string) $keeper->position) === 'Karyawan') && $item->position) {
                $keeper->position = $item->position;
            }
            if (($keeper->department === null || trim((string) $keeper->department) === '' || trim((string) $keeper->department) === 'Operasional') && $item->department) {
                $keeper->department = $item->department;
            }
        }

        $keeper->save();
        Employee::query()
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower(trim($name))])
            ->where('id', '!=', $keeper->id)
            ->delete();
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
            'finance',
            'kasir-atk',
            'kasir-wash',
            'karyawan-wash',
            'coordinator',
            'owner-pendiri',
        ];
    }

    public function departmentFromRole(?string $roleName): string
    {
        return match ($roleName) {
            'technician', 'noc', 'network-operations-center' => 'Teknis',
            'admin', 'owner-pendiri' => 'Administrasi',
            'finance' => 'Keuangan',
            'kasir-wash', 'karyawan-wash' => 'Wash',
            'kasir-atk' => 'ATK',
            'coordinator' => 'Operasional',
            default => 'Operasional',
        };
    }

    private function findEmployeeForUser(User $user): ?Employee
    {
        $byUserId = Employee::query()->where('user_id', $user->id)->orderByDesc('updated_at')->first();
        if ($byUserId) {
            return $byUserId;
        }

        $washEmployeeId = WashEmployee::query()
            ->where('user_id', $user->id)
            ->value('id');
        if ($washEmployeeId) {
            $byWashEmployee = Employee::query()->where('wash_employee_id', $washEmployeeId)->first();
            if ($byWashEmployee) {
                return $byWashEmployee;
            }
        }

        $email = strtolower(trim((string) $user->email));
        if ($email !== '') {
            $byEmail = Employee::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orderByDesc('updated_at')
                ->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        $name = strtolower(trim((string) $user->name));
        if ($name !== '') {
            $byName = Employee::query()
                ->whereNull('user_id')
                ->whereNull('wash_employee_id')
                ->whereRaw('LOWER(TRIM(full_name)) = ?', [$name])
                ->orderByDesc('updated_at')
                ->first();
            if ($byName) {
                return $byName;
            }
        }

        return null;
    }

    private function findEmployeeForWashEmployee(WashEmployee $washEmployee): ?Employee
    {
        $byWashId = Employee::query()->where('wash_employee_id', $washEmployee->id)->orderByDesc('updated_at')->first();
        if ($byWashId) {
            return $byWashId;
        }

        if ($washEmployee->user_id) {
            $byUserId = Employee::query()->where('user_id', $washEmployee->user_id)->orderByDesc('updated_at')->first();
            if ($byUserId) {
                return $byUserId;
            }
        }

        $name = strtolower(trim((string) $washEmployee->name));
        if ($name !== '') {
            $byName = Employee::query()
                ->whereNull('wash_employee_id')
                ->whereNull('user_id')
                ->whereRaw('LOWER(TRIM(full_name)) = ?', [$name])
                ->orderByDesc('updated_at')
                ->first();
            if ($byName) {
                return $byName;
            }
        }

        return null;
    }

    private function cleanupDuplicateEmployeesByUserId(int $userId): void
    {
        $items = Employee::query()
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get();

        if ($items->count() <= 1) {
            return;
        }

        $keeper = $items->sortByDesc(fn (Employee $employee) => $this->employeeQualityScore($employee))->first();
        foreach ($items as $item) {
            if ($item->id === $keeper->id) {
                continue;
            }

            if (! $keeper->wash_employee_id && $item->wash_employee_id) {
                $keeper->wash_employee_id = $item->wash_employee_id;
            }
            if (($keeper->phone === null || $keeper->phone === '' || $keeper->phone === '-') && $item->phone && $item->phone !== '-') {
                $keeper->phone = $item->phone;
            }
            if (($keeper->address === null || trim((string) $keeper->address) === '' || trim((string) $keeper->address) === '-') && $item->address && trim((string) $item->address) !== '-') {
                $keeper->address = $item->address;
            }
            if (($keeper->nik === null || trim((string) $keeper->nik) === '' || str_starts_with((string) $keeper->nik, 'AUTO-')) && $item->nik && ! str_starts_with((string) $item->nik, 'AUTO-')) {
                $keeper->nik = $item->nik;
            }
            if (($keeper->email === null || trim((string) $keeper->email) === '') && $item->email) {
                $keeper->email = $item->email;
            }
            if (($keeper->position === null || trim((string) $keeper->position) === '' || trim((string) $keeper->position) === 'Karyawan') && $item->position) {
                $keeper->position = $item->position;
            }
            if (($keeper->department === null || trim((string) $keeper->department) === '' || trim((string) $keeper->department) === 'Operasional') && $item->department) {
                $keeper->department = $item->department;
            }
        }

        $keeper->save();
        Employee::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $keeper->id)
            ->delete();
    }

    private function cleanupDuplicateEmployeesByWashEmployeeId(int $washEmployeeId): void
    {
        $items = Employee::query()
            ->where('wash_employee_id', $washEmployeeId)
            ->orderBy('id')
            ->get();

        if ($items->count() <= 1) {
            return;
        }

        $keeper = $items->sortByDesc(fn (Employee $employee) => $this->employeeQualityScore($employee))->first();
        foreach ($items as $item) {
            if ($item->id === $keeper->id) {
                continue;
            }

            if (! $keeper->user_id && $item->user_id) {
                $keeper->user_id = $item->user_id;
            }
            if (($keeper->phone === null || $keeper->phone === '' || $keeper->phone === '-') && $item->phone && $item->phone !== '-') {
                $keeper->phone = $item->phone;
            }
            if (($keeper->address === null || trim((string) $keeper->address) === '' || trim((string) $keeper->address) === '-') && $item->address && trim((string) $item->address) !== '-') {
                $keeper->address = $item->address;
            }
            if (($keeper->nik === null || trim((string) $keeper->nik) === '' || str_starts_with((string) $keeper->nik, 'AUTO-') || str_starts_with((string) $keeper->nik, 'WASH-')) && $item->nik && ! str_starts_with((string) $item->nik, 'AUTO-') && ! str_starts_with((string) $item->nik, 'WASH-')) {
                $keeper->nik = $item->nik;
            }
            if (($keeper->email === null || trim((string) $keeper->email) === '') && $item->email) {
                $keeper->email = $item->email;
            }
            if (($keeper->position === null || trim((string) $keeper->position) === '' || trim((string) $keeper->position) === 'Karyawan') && $item->position) {
                $keeper->position = $item->position;
            }
            if (($keeper->department === null || trim((string) $keeper->department) === '' || trim((string) $keeper->department) === 'Operasional') && $item->department) {
                $keeper->department = $item->department;
            }
        }

        $keeper->save();
        Employee::query()
            ->where('wash_employee_id', $washEmployeeId)
            ->where('id', '!=', $keeper->id)
            ->delete();
    }

    private function employeeQualityScore(Employee $employee): int
    {
        $score = 0;
        if ($employee->wash_employee_id) {
            $score += 100;
        }
        if ($employee->phone && $employee->phone !== '-') {
            $score += 20;
        }
        if ($employee->address && trim((string) $employee->address) !== '' && trim((string) $employee->address) !== '-') {
            $score += 15;
        }
        if ($employee->nik && ! str_starts_with((string) $employee->nik, 'AUTO-')) {
            $score += 20;
        }
        if ($employee->email && ! str_ends_with(strtolower((string) $employee->email), '@mstore.local')) {
            $score += 10;
        }
        if ($employee->position && ! in_array(strtolower((string) $employee->position), ['karyawan', 'technician'], true)) {
            $score += 5;
        }
        $score += (int) $employee->id / 1000;

        return $score;
    }
}
