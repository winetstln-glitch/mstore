<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'status' => 'required|in:present,late,leave,permit,sick,alpha',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ];
    }
}
