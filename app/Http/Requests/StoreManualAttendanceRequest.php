<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,leave,permit,sick,alpha',
            'clock_in_create' => 'nullable|date_format:H:i',
            'clock_out_create' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ];
    }
}
