<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBoothRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booth')->floorPlan);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $booth = $this->route('booth');

        return [
            'booth_number' => [
                'required', 'string', 'max:50',
                Rule::unique('booths', 'booth_number')->where('floor_plan_id', $booth->floor_plan_id)->ignore($booth->id),
            ],
            'organization_id' => [
                'nullable',
                Rule::exists('event_organization', 'organization_id')->where('event_id', $booth->floorPlan->event_id),
            ],
            'x' => ['required', 'numeric', 'between:0,100'],
            'y' => ['required', 'numeric', 'between:0,100'],
            'width' => ['required', 'numeric', 'between:0.5,100'],
            'height' => ['required', 'numeric', 'between:0.5,100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
