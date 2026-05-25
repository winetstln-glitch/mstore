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
            'from' => ['required', 'string'],
            'to' => ['required', 'string'],
            'message' => ['nullable', 'string'],
            'media_url' => ['nullable', 'url'],
            'media_type' => ['nullable', 'string'],
            'message_id' => ['required', 'string'],
            'timestamp' => ['required', 'integer'],
        ];
    }
}
