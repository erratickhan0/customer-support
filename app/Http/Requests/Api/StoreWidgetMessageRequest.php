<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWidgetMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'min:20', 'max:255'],
            'session_id' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
            'metadata.page_url' => ['nullable', 'url', 'max:2048'],
            'metadata.locale' => ['nullable', 'string', 'max:20'],
            'metadata.referrer' => ['nullable', 'url', 'max:2048'],
            'metadata.user_agent' => ['nullable', 'string', 'max:512'],
            'metadata.source' => ['nullable', 'string', Rule::in(['widget', 'api'])],
        ];
    }
}
