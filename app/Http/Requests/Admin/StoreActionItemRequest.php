<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskPriority;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActionItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Task::class, $this->route('meeting')->event]);
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
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
