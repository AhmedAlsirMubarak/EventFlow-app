<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreDocumentShareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('share', $this->route('document'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A document may only be shared with organizations/contacts linked to its own event —
     * a bare `exists:` would let it be shared with any organization/contact system-wide.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $eventId = $this->route('document')->event_id;

        return [
            'organization_id' => [
                'required_without:contact_id', 'nullable',
                Rule::exists('event_organization', 'organization_id')->where('event_id', $eventId),
            ],
            'contact_id' => [
                'required_without:organization_id', 'nullable',
                Rule::exists('contacts', 'id')->whereIn(
                    'organization_id',
                    DB::table('event_organization')->select('organization_id')->where('event_id', $eventId),
                ),
            ],
        ];
    }
}
