<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\WashEmployee;

class EmployeeSyncService
{
    public static function positionRoleDepartmentMap(): array
    {
        return [
            ['role' => Role::ADMIN,              'position' => 'Administrasi',     'department' => 'Administrasi', 'role_label' => 'Administrator'],
            ['role' => Role::DIREKTUR,           'position' => 'Direktur',         'department' => 'Administrasi', 'role_label' => 'Direktur'],
            ['role' => Role::HRD_MANAGER,        'position' => 'HRD Manager',      'department' => 'Administrasi', 'role_label' => 'HRD Manager'],
            ['role' => Role::LEADER,             'position' => 'Leader',           'department' => 'Operasional',  'role_label' => 'Leader'],
            ['role' => Role::CUSTOMER_SERVICE,   'position' => 'Customer Service', 'department' => 'Administrasi', 'role_label' => 'Customer Service'],
            ['role' => Role::NOC,                'position' => 'NOC',              'department' => 'Teknis',      'role_label' => 'Network Operations Center'],
            ['role' => Role::NOC_LEGACY,         'position' => 'NOC',              'department' => 'Teknis',      'role_label' => 'Network Operations Center'],
            ['role' => Role::TECHNICIAN,         'position' => 'Teknisi',          'department' => 'Teknis',      'role_label' => 'Technician'],
            ['role' => Role::COORDINATOR,        'position' => 'Koordinator',      'department' => 'Operasional',  'role_label' => 'Coordinator'],
            ['role' => Role::FINANCE,            'position' => 'Keuangan',         'department' => 'Keuangan',     'role_label' => 'Finance Staff'],
            ['role' => Role::KASIR_ATK,          'position' => 'Kasir ATK',        'department' => 'ATK',         'role_label' => 'Kasir ATK'],
            ['role' => Role::KASIR_WASH,         'position' => 'Kasir Wash',       'department' => 'Wash',        'role_label' => 'Kasir Wash'],
            ['role' => Role::KARYAWAN_WASH,      'position' => 'Operator Wash',    'department' => 'Wash',        'role_label' => 'Karyawan Wash'],
            ['role' => Role::STAFF_GUDANG,       'position' => 'Staff Gudang',     'department' => 'Operasional',  'role_label' => 'Staff Gudang'],
        ];
    }

    public static function allPositions(): array
    {
        $extras = ['Kasir'];
        $fromMap = collect(self::positionRoleDepartmentMap())
            ->pluck('position')
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($fromMap, $extras)));
    }

    public static function allDepartments(): array
    {
        return ['Administrasi', 'Keuangan', 'Teknis', 'Wash', 'ATK', 'Operasional'];
    }

    public function positionFromRole(?string $roleName): ?string
    {
        if ($roleName === null) {
            return null;
        }
        $roleNameLower = strtolower(trim($roleName));
        foreach (self::positionRoleDepartmentMap() as $item) {
            if (strtolower($item['role']) === $roleNameLower) {
                return $item['position'];
            }
        }

        return null;
    }

    public function roleNameFromPosition(?string $position): ?string
    {
        if ($position === null || trim($position) === '') {
            return null;
        }
        $posLower = strtolower(trim($position));
        foreach (self::positionRoleDepartmentMap() as $item) {
            if (strtolower($item['position']) === $posLower) {
                return $item['role'];
            }
        }

        return null;
    }

    public function departmentFromPosition(?string $position): string
    {
        if ($position === null || trim($position) === '') {
            return 'Operasional';
        }
        $posLower = strtolower(trim($position));
        foreach (self::positionRoleDepartmentMap() as $item) {
            if (strtolower($item['position']) === $posLower) {
                return $item['department'];
            }
        }

        return 'Operasional';
    }

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
        $mappedPosition = $this->positionFromRole($user->role?->name);
        $employee->position = $mappedPosition ?: ($user->role?->label ?: ($employee->position ?: 'Karyawan'));
        $employee->department = $this->departmentFromRole($user->role?->name);
        
        // Sync salary fields from User to Employee, only if Employee fields are empty
        if (empty($employee->monthly_salary) || $employee->monthly_salary == 0) {
            $employee->monthly_salary = $user->monthly_salary ?? 0;
        }
        if (empty($employee->daily_salary) || $employee->daily_salary == 0) {
            $employee->daily_salary = $user->daily_salary ?? 0;
        }

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
        $this->syncWashEmployeeFromUser($user);
    }

    public function syncWashEmployeeFromUser(User $user): void
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        $isKaryawanWash = $roleName === strtolower(Role::KARYAWAN_WASH);
        $isActive = (bool) $user->is_active;

        try {
            $washEmployee = WashEmployee::query()->where('user_id', $user->id)->first();
            if ($isKaryawanWash && $isActive) {
                if (! $washEmployee) {
                    WashEmployee::query()->create([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone ?: '-',
                        'status' => 'active',
                    ]);
                } else {
                    $updates = [];
                    if ($washEmployee->status !== 'active') {
                        $updates['status'] = 'active';
                    }
                    $trimmedName = trim((string) $washEmployee->name);
                    if ($trimmedName === '' && trim((string) $user->name) !== '') {
                        $updates['name'] = $user->name;
                    }
                    if (! empty($updates)) {
                        $washEmployee->update($updates);
                    }
                }
            } elseif ($washEmployee) {
                if (! $isActive && $washEmployee->status === 'active') {
                    $washEmployee->update(['status' => 'inactive']);
                } elseif (! $isKaryawanWash && $washEmployee->status === 'active') {
                    $washEmployee->update(['status' => 'inactive']);
                }
            }
        } catch (\Throwable) {
        }
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
        return $roleName !== '' && $roleName !== 'customer';
    }

    public function allowedRoles(): array
    {
        return Role::query()
            ->where('name', '!=', 'customer')
            ->pluck('name')
            ->map(fn ($name) => strtolower((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function departmentFromRole(?string $roleName): string
    {
        if ($roleName === null) {
            return 'Operasional';
        }
        $roleNameLower = strtolower(trim($roleName));
        foreach (self::positionRoleDepartmentMap() as $item) {
            if (strtolower($item['role']) === $roleNameLower) {
                return $item['department'];
            }
        }

        return match ($roleNameLower) {
            'reseller', 'owner-pendiri' => 'Operasional',
            default => 'Operasional',
        };
    }

    public function syncFromEmployee(Employee $employee): void
    {
        if (empty($employee->user_id)) {
            return;
        }

        $user = User::query()->find($employee->user_id);
        if (! $user) {
            return;
        }

        $roleName = $this->roleNameFromPosition($employee->position);
        if ($roleName !== null) {
            $role = \App\Models\Role::query()->where('name', $roleName)->first();
            if ($role && (string) $user->role_id !== (string) $role->id) {
                $user->role_id = $role->id;
                $user->save();
            }
        }
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

    public function ensureWashEmployeesFromUsers(): array
    {
        $result = ['created' => 0, 'updated' => 0, 'deactivated' => 0, 'errors' => []];

        $roleIdKaryawanWash = Role::query()->where('name', Role::KARYAWAN_WASH)->value('id');
        if (! $roleIdKaryawanWash) {
            $result['errors'][] = 'Role '.Role::KARYAWAN_WASH.' tidak ditemukan';

            return $result;
        }

        $usersKaryawanWash = User::query()
            ->where('is_active', true)
            ->where('role_id', $roleIdKaryawanWash)
            ->get(['id', 'name', 'phone', 'email']);

        foreach ($usersKaryawanWash as $user) {
            try {
                $existing = WashEmployee::query()->where('user_id', $user->id)->first();
                if (! $existing) {
                    WashEmployee::query()->create([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone ?: '-',
                        'status' => 'active',
                    ]);
                    $result['created']++;
                } else {
                    $needUpdate = false;
                    $updates = [];
                    if ($existing->status !== 'active') {
                        $updates['status'] = 'active';
                        $needUpdate = true;
                    }
                    $nameTrim = trim((string) $existing->name);
                    if ($nameTrim === '' && trim((string) $user->name) !== '') {
                        $updates['name'] = $user->name;
                        $needUpdate = true;
                    }
                    if ($needUpdate) {
                        $existing->update($updates);
                        $result['updated']++;
                    }
                }
            } catch (\Throwable $e) {
                $result['errors'][] = 'user_id='.$user->id.': '.get_class($e).': '.$e->getMessage();
            }
        }

        try {
            $toBeDeactivated = WashEmployee::query()
                ->whereNotNull('user_id')
                ->where('status', 'active')
                ->whereHas('user', function ($q) {
                    $q->where('is_active', false);
                });
            $countToDeactivate = $toBeDeactivated->count();
            if ($countToDeactivate > 0) {
                $toBeDeactivated->update(['status' => 'inactive']);
                $result['deactivated'] = $countToDeactivate;
            }
        } catch (\Throwable $e) {
            $result['errors'][] = 'deactivate: '.get_class($e).': '.$e->getMessage();
        }

        return $result;
    }

    public function getActiveWashEmployeesForDropdown()
    {
        return WashEmployee::query()
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'inactive');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
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
        if ($employee->nik && ! str_starts_with((string) $employee->nik, 'AUTO-') && ! str_starts_with((string) $employee->nik, 'WASH-')) {
            $score += 20;
        }
        if ($employee->email && ! str_ends_with(strtolower((string) $employee->email), '@mstore.local')) {
            $score += 10;
        }
        $badPositions = ['karyawan', '', null];
        if ($employee->position && ! in_array(strtolower(trim((string) $employee->position)), $badPositions, true)) {
            $score += 5;
        }
        $score += (int) $employee->id / 1000;

        return $score;
    }
}
