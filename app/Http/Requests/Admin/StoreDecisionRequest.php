<?php

namespace App\Http\Requests\Admin;

use App\Models\Decision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDecisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Decision::class, $this->route('event')]);
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
            'decision_date' => ['required', 'date'],
            'decided_by' => ['nullable', 'exists:users,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'meeting_id' => ['nullable', 'exists:meetings,id'],
            'document_id' => ['nullable', 'exists:documents,id'],
            'reason' => ['nullable', 'string'],
            'supporting_document_ids' => ['array'],
            'supporting_document_ids.*' => ['exists:documents,id'],
        ];
    }
}
