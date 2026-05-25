<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Setting;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $photoMaxKb = $this->resolveAttendancePhotoMaxKb();

        return [
            'photo' => 'nullable|image|max:' . $photoMaxKb,
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'device_fingerprint' => 'nullable|string|min:8|max:128',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        $photoMaxKb = $this->resolveAttendancePhotoMaxKb();

        return [
            'photo.max' => __('Ukuran foto terlalu besar. Maksimal :max KB.', ['max' => $photoMaxKb]),
        ];
    }

    private function resolveAttendancePhotoMaxKb(): int
    {
        $group = $this->user() ? $this->resolveUserGroup($this->user()) : 'teknisi';
        $settingKey = $group === 'wash'
            ? 'attendance_photo_max_kb_wash'
            : 'attendance_photo_max_kb';

        return (int) Setting::getValue($settingKey, 2048);
    }

    private function resolveUserGroup($user): string
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
            return 'wash';
        }
        if (\Schema::hasTable('wash_employees') && \App\Models\WashEmployee::where('user_id', $user->id)->exists()) {
            return 'wash';
        }
        return 'teknisi';
    }
}
