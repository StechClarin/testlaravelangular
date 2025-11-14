<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255',Rule::unique('tasks')->where('created_by', $this->user()->id)],
            'description' => ['nullable', 'string'],
            // --- CORRECTION ICI ---
            'status' => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            // --- FIN CORRECTION ---
            'due_date' => ['required', 'date'], // Ta migration dit qu'elle est requise
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}