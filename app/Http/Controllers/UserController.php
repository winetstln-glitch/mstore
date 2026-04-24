<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Traits\HasIdCard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class UserController extends Controller implements HasMiddleware
{
    use HasIdCard;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:user.view', only: ['index', 'export', 'idCard']),
            new Middleware('permission:user.create', only: ['create', 'store']),
            new Middleware('permission:user.edit', only: ['edit', 'update', 'sendWhatsAppAccount']),
            new Middleware('permission:user.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('name', '!=', 'customer');
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('attendance_card_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::orderBy('label')->get();
        $waLogQuery = DB::table('whatsapp_account_send_logs as logs')
            ->leftJoin('users as sender', 'sender.id', '=', 'logs.sender_user_id')
            ->leftJoin('users as target', 'target.id', '=', 'logs.target_user_id')
            ->select([
                'logs.id',
                'logs.sender_user_id',
                'logs.target_phone',
                'logs.status',
                'logs.message_excerpt',
                'logs.error_message',
                'logs.created_at',
                'sender.name as sender_name',
                'target.name as target_name',
            ]);

        if ($request->filled('wa_status')) {
            $waLogQuery->where('logs.status', (string) $request->input('wa_status'));
        }
        if ($request->filled('wa_sender')) {
            $waLogQuery->where('logs.sender_user_id', (int) $request->input('wa_sender'));
        }
        if ($request->filled('wa_date_from')) {
            $waLogQuery->whereDate('logs.created_at', '>=', (string) $request->input('wa_date_from'));
        }
        if ($request->filled('wa_date_to')) {
            $waLogQuery->whereDate('logs.created_at', '<=', (string) $request->input('wa_date_to'));
        }
        if ($request->filled('wa_q')) {
            $waSearch = (string) $request->input('wa_q');
            $waLogQuery->where(function ($q) use ($waSearch) {
                $q->where('target.name', 'like', "%{$waSearch}%")
                    ->orWhere('sender.name', 'like', "%{$waSearch}%")
                    ->orWhere('logs.target_phone', 'like', "%{$waSearch}%")
                    ->orWhere('logs.message_excerpt', 'like', "%{$waSearch}%");
            });
        }

        $whatsAppAccountLogs = $waLogQuery
            ->orderByDesc('logs.id')
            ->paginate(12, ['*'], 'wa_logs_page')
            ->withQueryString();

        $waSenders = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', '!=', 'customer');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('users.index', compact('users', 'roles', 'whatsAppAccountLogs', 'waSenders'));
    }

    public function export(Request $request)
    {
        $query = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('name', '!=', 'customer');
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('attendance_card_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        $users = $query->get();

        return response()->streamDownload(function () use ($users) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'ID',
                'Name',
                'Email',
                'Username',
                'Attendance Card Code',
                'Role',
                'Phone',
                'Daily Salary',
                'Status',
                'Created At',
            ]));

            foreach ($users as $user) {
                $writer->addRow(Row::fromValues([
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->username,
                    $user->attendance_card_code,
                    $user->role ? $user->role->label : 'No Role',
                    $user->phone,
                    $user->daily_salary,
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]));
            }

            $writer->close();
        }, 'users-'.date('Y-m-d-His').'.xlsx');
    }

    public function idCard(User $user)
    {
        $idCardCode = $this->userIdCardCode($user);
        $employee = $user->employee;
        [$brandName, $logoUrl, $brandSlogan, $brandKey] = $this->resolveUserBrand($user);

        return view('users.id-card', compact('user', 'logoUrl', 'brandName', 'brandSlogan', 'brandKey', 'idCardCode', 'employee'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'customer')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $customerRoleId = Role::where('name', 'customer')->value('id');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'radius_username' => ['nullable', 'string', 'max:255', 'unique:users,radius_username'],
            'role_id' => ['required', 'exists:roles,id', Rule::notIn([$customerRoleId])],
            'phone' => ['nullable', 'string', 'max:20'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if ($this->hasAttendanceCardColumn()) {
            $rules['attendance_card_code'] = ['nullable', 'string', 'max:255', 'unique:users,attendance_card_code'];
        }
        $validated = $request->validate($rules);

        // Prevent duplicates by checking name too
        $existing = User::findExistingUser([
            'email' => $validated['email'] ?? null,
            'radius_username' => $validated['radius_username'] ?? null,
            'attendance_card_code' => $validated['attendance_card_code'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($existing) {
            return back()->withErrors(['name' => __('User with similar information already exists: :name (:email)', ['name' => $existing->name, 'email' => $existing->email ?: $existing->username])])->withInput();
        }

        $username = $this->buildUsernameFromName($validated['name'], $validated['email'] ?? null);

        $createData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'username' => $username,
            'radius_username' => $validated['radius_username'] ?? null,
            'password' => Hash::make('12345678'),
            'role_id' => $validated['role_id'],
            'phone' => $validated['phone'] ?? null,
            'daily_salary' => $validated['daily_salary'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
        if ($this->hasAttendanceCardColumn()) {
            $createData['attendance_card_code'] = trim((string) ($validated['attendance_card_code'] ?? ''));
        }
        $createdUser = User::create($createData);

        if ($this->hasAttendanceCardColumn() && trim((string) $createdUser->attendance_card_code) === '') {
            $createdUser->update([
                'attendance_card_code' => User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $createdUser->id), (int) $createdUser->id),
            ]);
        }

        return redirect()->route('users.index')->with('success', __('User created successfully.'));
    }

    public function edit(User $user)
    {
        $roles = Role::where('name', '!=', 'customer')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $customerRoleId = Role::where('name', 'customer')->value('id');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'radius_username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'radius_username')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id', Rule::notIn([$customerRoleId])],
            'phone' => ['nullable', 'string', 'max:20'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if ($this->hasAttendanceCardColumn()) {
            $rules['attendance_card_code'] = ['nullable', 'string', 'max:255', Rule::unique('users', 'attendance_card_code')->ignore($user->id)];
        }
        $validated = $request->validate($rules);

        $existing = User::findExistingUser([
            'email' => $validated['email'] ?? null,
            'radius_username' => $validated['radius_username'] ?? null,
            'attendance_card_code' => $validated['attendance_card_code'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);
        if ($existing && (int) $existing->id !== (int) $user->id) {
            return back()->withErrors(['name' => __('User with similar information already exists: :name (:email)', ['name' => $existing->name, 'email' => $existing->email ?: $existing->username])])->withInput();
        }

        $username = $this->buildUsernameFromName($validated['name'], $validated['email'] ?? null, (int) $user->id);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'username' => $username,
            'radius_username' => $validated['radius_username'] ?? null,
            'role_id' => $validated['role_id'],
            'phone' => $validated['phone'] ?? null,
            'daily_salary' => $validated['daily_salary'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
        if ($this->hasAttendanceCardColumn()) {
            $updateData['attendance_card_code'] = trim((string) ($validated['attendance_card_code'] ?? '')) ?: User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $user->id), (int) $user->id);
        }
        $user->update($updateData);

        \App\Models\Employee::query()
            ->where('user_id', $user->id)
            ->update([
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        if ($request->boolean('reset_default_password')) {
            $user->update([
                'password' => Hash::make('12345678'),
            ]);
        }

        return redirect()->route('users.index')->with('success', __('User updated successfully.'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot delete yourself.'));
        }

        try {
            DB::transaction(function () use ($user) {
                $user->loadMissing('employee');

                if ($user->employee) {
                    if ($user->employee->document_path) {
                        Storage::disk('public')->delete($user->employee->document_path);
                    }
                    if ($user->employee->id_card_photo_path) {
                        Storage::disk('public')->delete($user->employee->id_card_photo_path);
                    }
                    $user->employee->forceDelete();
                }

                $user->delete();
            });

            return redirect()->route('users.index')->with('success', __('User deleted successfully.'));
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', __('Cannot delete user because they have related records (e.g., attendance, transactions, logs).'));
            }
            throw $e;
        }
    }

    public function sendWhatsAppAccount(User $user, WhatsAppService $wa)
    {
        $validated = request()->validate([
            'send_password' => ['nullable', 'string', 'max:100'],
        ]);

        if (! $user->phone) {
            return back()->with('error', __('Pengguna tidak memiliki nomor HP.'));
        }

        $phone = $this->normalizePhone((string) $user->phone);
        if ($phone === '') {
            return back()->with('error', __('Nomor HP pengguna tidak valid.'));
        }

        $passwordToSend = trim((string) ($validated['send_password'] ?? ''));
        if ($passwordToSend === '') {
            $passwordToSend = '12345678';
        }

        $vars = [
            'nama' => (string) $user->name,
            'username' => (string) ($user->username ?: '-'),
            'email' => (string) ($user->email ?: '-'),
            'peran' => (string) ($user->role?->label ?: 'Tanpa Peran'),
            'nomor_hp' => (string) ($user->phone ?: '-'),
            'status' => $user->is_active ? 'Aktif' : 'Tidak Aktif',
            'password' => $passwordToSend,
            'login_url' => route('login'),
        ];

        $tpl = Setting::where('key', 'whatsapp_user_account_template')->value('value')
            ?? "*INFORMASI AKUN SISTEM*\n\nNama: {{nama}}\nUsername: {{username}}\nEmail: {{email}}\nNomor HP: {{nomor_hp}}\nPeran: {{peran}}\nStatus: {{status}}\nPassword: {{password}}\n\nLogin: {{login_url}}\n\nMohon simpan informasi akun ini dengan aman.";

        $message = $wa->renderTemplate($tpl, $vars);
        $auditId = DB::table('whatsapp_account_send_logs')->insertGetId([
            'sender_user_id' => auth()->id(),
            'target_user_id' => $user->id,
            'target_phone' => $phone,
            'status' => 'pending',
            'message_excerpt' => 'Informasi akun dikirim untuk username: '.($user->username ?: '-'),
            'password_included' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gatewayStatus = $wa->checkGatewayStatus();
        if (! ($gatewayStatus['ok'] ?? false) || ! ($gatewayStatus['connected'] ?? false)) {
            $gatewayError = mb_substr((string) ($gatewayStatus['message'] ?? 'Gateway WhatsApp belum siap.'), 0, 500);
            DB::table('whatsapp_account_send_logs')->where('id', $auditId)->update([
                'status' => 'failed',
                'error_message' => $gatewayError,
                'updated_at' => now(),
            ]);

            return back()->with('error', __('Gagal mengirim WhatsApp: :message', ['message' => $gatewayError]));
        }

        try {
            $wa->sendMessage($phone, $message, 'user_account');
        } catch (\Throwable $e) {
            DB::table('whatsapp_account_send_logs')->where('id', $auditId)->update([
                'status' => 'failed',
                'error_message' => mb_substr((string) $e->getMessage(), 0, 500),
                'updated_at' => now(),
            ]);

            return back()->with('error', __('Gagal mengirim WhatsApp: :message', ['message' => $e->getMessage()]));
        }

        DB::table('whatsapp_account_send_logs')->where('id', $auditId)->update([
            'status' => 'sent',
            'error_message' => null,
            'updated_at' => now(),
        ]);

        return back()->with('success', __('Informasi akun berhasil dikirim ke gateway WhatsApp.'));
    }

    private function hasAttendanceCardColumn(): bool
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('users_attendance_card_column', function () {
            return Schema::hasColumn('users', 'attendance_card_code');
        });
    }

    private function buildUsernameFromName(string $name, ?string $fallbackEmail = null, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($base === '' && is_string($fallbackEmail) && $fallbackEmail !== '') {
            $base = \Illuminate\Support\Str::before(strtolower($fallbackEmail), '@');
            $base = \Illuminate\Support\Str::of($base)
                ->ascii()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->value();
        }

        if ($base === '') {
            $base = 'user';
        }

        $base = mb_substr($base, 0, 40);
        $candidate = $base;
        $suffix = 1;

        while (User::query()
            ->where('username', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $suffixText = '_'.$suffix;
            $candidate = mb_substr($base, 0, max(1, 40 - strlen($suffixText))).$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! is_string($digits) || $digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            if (strlen($digits) < 10 || strlen($digits) > 16) {
                return '';
            }

            return $digits;
        }

        $normalized = '62'.$digits;
        if (strlen($normalized) < 10 || strlen($normalized) > 16) {
            return '';
        }

        return $normalized;
    }
}
