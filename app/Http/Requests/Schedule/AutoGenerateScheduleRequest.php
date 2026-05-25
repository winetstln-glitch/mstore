<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class AutoGenerateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'shift1_slots' => ['nullable', 'integer', 'min:1', 'max:50'],
            'shift2_slots' => ['nullable', 'integer', 'min:1', 'max:50'],
            'longshift_slots' => ['nullable', 'integer', 'min:0', 'max:50'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ];
    }
}
