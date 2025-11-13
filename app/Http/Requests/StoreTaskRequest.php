<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La permission est gérée par le Middleware/Route, ici on autorise si l'user est connecté
        return true; 
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], // [cite: 34]
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])], // [cite: 35]
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])], // [cite: 36]
            'due_date' => ['nullable', 'date', 'after:today'], // [cite: 37]
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'], // [cite: 38]
        ];
    }
}