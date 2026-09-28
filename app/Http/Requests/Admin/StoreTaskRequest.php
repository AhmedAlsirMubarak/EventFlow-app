<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Task::class, $this->route('event')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'start_date' => ['nullable', 'date', ...($this->filled('due_date') ? ['before_or_equal:due_date'] : [])],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_column(TaskStatus::cases(), 'value'))],
        ];
    }
}
