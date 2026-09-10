<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachEventMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('event'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Only internal staff belong on an event's team — external stakeholders are
     * managed as Contacts/committee members instead.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', Rule::exists('users', 'id')->where('user_type', UserType::Internal)],
            'role_in_event' => ['nullable', Rule::in(array_column(Role::cases(), 'value'))],
        ];
    }
}
