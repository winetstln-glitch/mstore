<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class WebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Support multiple formats (Fonnte, Wablas, generic)
            'from' => ['nullable', 'string'],
            'to' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
            'media_url' => ['nullable', 'url'],
            'media_type' => ['nullable', 'string'],
            'message_id' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'integer'],
            'data' => ['nullable', 'array'],
            'data.*' => ['nullable', 'array'],
        ];
    }
}
