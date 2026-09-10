<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
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
            'held_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:2048'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'agenda' => ['nullable', 'string'],
            'discussion_notes' => ['nullable', 'string'],
            'participant_ids' => ['array'],
            'participant_ids.*' => ['exists:users,id'],
            'external_participant_ids' => ['array'],
            'external_participant_ids.*' => ['exists:contacts,id'],
        ];
    }
}
