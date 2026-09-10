<?php

namespace App\Http\Requests\Admin;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Meeting::class, $this->route('event')]);
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
