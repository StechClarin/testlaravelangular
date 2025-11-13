<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed'])],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])],
            'due_date' => ['nullable', 'date', 'after:today'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}