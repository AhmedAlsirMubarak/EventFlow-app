<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApprovalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('document'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
