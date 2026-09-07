<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Document::class, $this->route('event')]);
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
            'category' => ['required', Rule::in(array_column(DocumentCategory::cases(), 'value'))],
            'visibility' => ['required', Rule::in(array_column(DocumentVisibility::cases(), 'value'))],
            'acknowledgment_required' => ['boolean'],
            'approval_required' => ['boolean'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png'],
        ];
    }
}
