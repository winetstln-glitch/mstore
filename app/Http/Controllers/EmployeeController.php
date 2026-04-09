<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WashEmployee;
use App\Services\EmployeeSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class EmployeeController extends Controller implements HasMiddleware
{
    public function __construct(private readonly EmployeeSyncService $employeeSyncService) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $department = trim((string) $request->query('department', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Employee::query()
            ->with(['user.role', 'washEmployee'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('nik', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('department', 'like', '%'.$search.'%')
                        ->orWhere('position', 'like', '%'.$search.'%');
                });
            })
            ->when($department !== '', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('employment_status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $employees = $query;
        $departments = Employee::query()->select('department')->whereNotNull('department')->distinct()->orderBy('department')->pluck('department');
        $statuses = ['Tetap', 'Kontrak', 'Magang'];

        return view('employees.index', compact('employees', 'search', 'departments', 'statuses', 'department', 'status'));
    }

    public function create()
    {
        $users = $this->employeeUsers();
        $washEmployees = $this->washEmployees();

        return view('employees.create', compact('users', 'washEmployees'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateEmployee($request);
        $validated['document_path'] = $this->storeDocument($request);
        if ($this->hasIdCardColumns()) {
            $validated['id_card_photo_path'] = $this->storeIdCardPhoto($request);
        } else {
            unset($validated['id_card_expires_at']);
        }

        $validated = $this->applyLinkedEmployee($validated);
        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        $users = $this->employeeUsers($employee->id);
        $washEmployees = $this->washEmployees($employee->id);

        return view('employees.edit', compact('employee', 'users', 'washEmployees'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployee($request, $employee->id);
        if (! $this->hasIdCardColumns()) {
            unset($validated['id_card_expires_at']);
        }
        $newDocPath = $this->storeDocument($request);
        if ($newDocPath) {
            if ($employee->document_path) {
                Storage::disk('public')->delete($employee->document_path);
            }
            $validated['document_path'] = $newDocPath;
        }
        if ($this->hasIdCardColumns()) {
            $newPhotoPath = $this->storeIdCardPhoto($request);
            if ($newPhotoPath) {
                if ($employee->id_card_photo_path) {
                    Storage::disk('public')->delete($employee->id_card_photo_path);
                }
                $validated['id_card_photo_path'] = $newPhotoPath;
            }
        }

        $validated = $this->applyLinkedEmployee($validated);
        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->document_path) {
            Storage::disk('public')->delete($employee->document_path);
        }
        if ($this->hasIdCardColumns() && $employee->id_card_photo_path) {
            Storage::disk('public')->delete($employee->id_card_photo_path);
        }
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil dihapus.');
    }

    public function idCard(Employee $employee, Request $request)
    {
        $idCardCode = $this->employeeIdCardCode($employee);
        $printMode = $request->boolean('print');
        [$brandName, $logoUrl, $brandSlogan] = $this->resolveEmployeeBrand($employee);

        return view('employees.id-card', compact('employee', 'idCardCode', 'printMode', 'logoUrl', 'brandName', 'brandSlogan'));
    }

    public function printCards(Request $request)
    {
        $selectedIds = collect((array) $request->query('selected_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $employees = $selectedIds->isNotEmpty()
            ? Employee::query()->whereIn('id', $selectedIds)->orderBy('full_name')->get()
            : $this->filteredEmployees($request)->orderBy('full_name')->get();
        $cards = $employees->map(function (Employee $employee) {
            [$brandName, $logoUrl, $brandSlogan] = $this->resolveEmployeeBrand($employee);

            return [
                'employee' => $employee,
                'code' => $this->employeeIdCardCode($employee),
                'brand_name' => $brandName,
                'logo_url' => $logoUrl,
                'brand_slogan' => $brandSlogan,
            ];
        });

        return view('employees.id-cards-print', compact('cards'));
    }

    public function exportCsv(Request $request)
    {
        $filename = 'data-karyawan-'.date('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $search = trim((string) $request->query('search', ''));
        $department = trim((string) $request->query('department', ''));
        $status = trim((string) $request->query('status', ''));

        $rows = Employee::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('nik', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('department', 'like', '%'.$search.'%')
                        ->orWhere('position', 'like', '%'.$search.'%');
                });
            })
            ->when($department !== '', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('employment_status', $status);
            })
            ->orderBy('full_name')
            ->get([
                'full_name',
                'date_of_birth',
                'gender',
                'address',
                'phone',
                'email',
                'nik',
                'position',
                'department',
                'join_date',
                'employment_status',
            ]);

        $columns = ['Nama Lengkap', 'Tanggal Lahir', 'Jenis Kelamin', 'Alamat', 'No HP', 'Email', 'NIK', 'Jabatan', 'Departemen', 'Tanggal Masuk', 'Status'];

        $callback = function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->full_name,
                    optional($r->date_of_birth)->format('Y-m-d'),
                    $r->gender,
                    $r->address,
                    $r->phone,
                    $r->email,
                    $r->nik,
                    $r->position,
                    $r->department,
                    optional($r->join_date)->format('Y-m-d'),
                    $r->employment_status,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $employees = $this->filteredEmployees($request)->orderBy('full_name')->get();
        $logo = Setting::getValue('store_logo');
        if (! $logo) {
            $logo = asset('img/logo.png');
        } elseif (! str_starts_with($logo, 'http')) {
            $logo = asset($logo);
        }

        $pdf = Pdf::loadView('employees.pdf', [
            'employees' => $employees,
            'logo' => $logo,
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('data_karyawan_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $employees = $this->filteredEmployees($request)->orderBy('full_name')->get();

        return response()->streamDownload(function () use ($employees) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Nama Lengkap',
                'Tanggal Lahir',
                'Jenis Kelamin',
                'Alamat',
                'No HP',
                'Email',
                'NIK',
                'Jabatan',
                'Departemen',
                'Tanggal Masuk',
                'Status',
                'Tipe Integrasi',
            ]));

            foreach ($employees as $employee) {
                $integration = [];
                if ($employee->user_id) {
                    $integration[] = 'User';
                }
                if ($employee->wash_employee_id) {
                    $integration[] = 'Wash';
                }
                if ($integration === []) {
                    $integration[] = 'Manual';
                }

                $writer->addRow(Row::fromValues([
                    $employee->full_name,
                    optional($employee->date_of_birth)->format('Y-m-d'),
                    $employee->gender,
                    $employee->address,
                    $employee->phone,
                    $employee->email,
                    $employee->nik,
                    $employee->position,
                    $employee->department,
                    optional($employee->join_date)->format('Y-m-d'),
                    $employee->employment_status,
                    implode(', ', $integration),
                ]));
            }

            $writer->close();
        }, 'data-karyawan-'.now()->format('Ymd-His').'.xlsx');
    }

    public function syncExisting()
    {
        $users = User::query()->with('role')->whereHas('role', function ($query) {
            $query->whereIn('name', $this->employeeSyncService->allowedRoles());
        })->get();

        foreach ($users as $user) {
            $this->employeeSyncService->syncFromUser($user);
        }

        $washEmployees = WashEmployee::query()->with('user')->get();
        foreach ($washEmployees as $washEmployee) {
            $this->employeeSyncService->syncFromWashEmployee($washEmployee);
        }

        return redirect()->route('employees.index')->with('success', 'Sinkronisasi data karyawan dari teknisi/wash/user berhasil.');
    }

    private function validateEmployee(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'wash_employee_id' => ['nullable', 'exists:wash_employees,id'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'nik' => ['required', 'string', 'max:32'],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', Rule::in(['Tetap', 'Kontrak', 'Magang'])],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
            'id_card_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'id_card_expires_at' => ['nullable', 'date'],
        ]);
    }

    private function storeDocument(Request $request): ?string
    {
        if (! $request->hasFile('document')) {
            return null;
        }

        return $request->file('document')->store('employee-documents', 'public');
    }

    private function storeIdCardPhoto(Request $request): ?string
    {
        if (! $this->hasIdCardColumns()) {
            return null;
        }
        if (! $request->hasFile('id_card_photo')) {
            return null;
        }

        return $request->file('id_card_photo')->store('employee-id-cards', 'public');
    }

    private function employeeUsers(?int $excludeEmployeeId = null)
    {
        $linkedUserIds = Employee::query()
            ->when($excludeEmployeeId, fn($q) => $q->where('id', '!=', $excludeEmployeeId))
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();

        return User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->whereIn('name', $this->employeeSyncService->allowedRoles());
            })
            ->whereNotIn('id', $linkedUserIds)
            ->orderBy('name')
            ->get();
    }

    private function washEmployees(?int $excludeEmployeeId = null)
    {
        $linkedWashIds = Employee::query()
            ->when($excludeEmployeeId, fn($q) => $q->where('id', '!=', $excludeEmployeeId))
            ->whereNotNull('wash_employee_id')
            ->pluck('wash_employee_id')
            ->toArray();

        return WashEmployee::query()
            ->whereNotIn('id', $linkedWashIds)
            ->orderBy('name')
            ->get();
    }

    private function applyLinkedEmployee(array $validated): array
    {
        if (! empty($validated['wash_employee_id'])) {
            $washEmployee = WashEmployee::query()->find($validated['wash_employee_id']);
            if ($washEmployee && empty($validated['user_id']) && $washEmployee->user_id) {
                $validated['user_id'] = $washEmployee->user_id;
            }
        }

        return $validated;
    }

    private function filteredEmployees(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $department = trim((string) $request->query('department', ''));
        $status = trim((string) $request->query('status', ''));

        return Employee::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('nik', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('department', 'like', '%'.$search.'%')
                        ->orWhere('position', 'like', '%'.$search.'%');
                });
            })
            ->when($department !== '', function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('employment_status', $status);
            });
    }

    private function employeeIdCardCode(Employee $employee): string
    {
        if (! empty($employee->user?->attendance_card_code)) {
            return (string) $employee->user->attendance_card_code;
        }

        if (! empty($employee->nik)) {
            return 'EMP-'.preg_replace('/[^0-9A-Za-z]/', '', (string) $employee->nik);
        }

        return 'EMP-'.str_pad((string) $employee->id, 5, '0', STR_PAD_LEFT);
    }

    private function resolveEmployeeBrand(Employee $employee): array
    {
        $scope = strtolower(trim((string) ($employee->department ?: $employee->position ?: '')));
        $defaultLogo = (string) (Setting::getValue('store_logo') ?: '');
        $defaultSlogan = 'Solusi Digital Cepat dan Terpercaya';
        if (str_contains($scope, 'wash')) {
            $name = (string) (Setting::getValue('brand_gtwash_name') ?: 'GTWASH');
            $logo = (string) (Setting::getValue('brand_gtwash_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_gtwash_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan];
        }
        if (str_contains($scope, 'net') || str_contains($scope, 'network') || str_contains($scope, 'internet')) {
            $name = (string) (Setting::getValue('brand_mstorenet_name') ?: 'MSTORE.NET');
            $logo = (string) (Setting::getValue('brand_mstorenet_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_mstorenet_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan];
        }

        $name = (string) (Setting::getValue('brand_mstore_name') ?: Setting::getValue('store_name') ?: 'MSTORE');
        $logo = (string) (Setting::getValue('brand_mstore_logo') ?: $defaultLogo);
        $slogan = (string) (Setting::getValue('brand_mstore_slogan') ?: $defaultSlogan);

        return [strtoupper($name), $this->brandLogoUrl($logo), $slogan];
    }

    private function hasIdCardColumns(): bool
    {
        return Schema::hasColumn('employees', 'id_card_photo_path')
            && Schema::hasColumn('employees', 'id_card_expires_at');
    }

    private function brandLogoUrl(string $logo): string
    {
        if (! $logo) {
            return asset('img/logo.png');
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return asset($logo);
    }
}
