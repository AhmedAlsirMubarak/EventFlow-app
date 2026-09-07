<?php

namespace App\Http\Requests\Admin;

use App\Enums\SubmissionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideAbstractSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('abstractSubmission'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                SubmissionStatus::Accepted->value,
                SubmissionStatus::Rejected->value,
                SubmissionStatus::Waitlisted->value,
            ])],
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
