<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskPriority;
use App\Models\StakeholderRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStakeholderRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [StakeholderRequest::class, $this->route('event')]);
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
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
