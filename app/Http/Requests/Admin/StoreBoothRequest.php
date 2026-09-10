<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBoothRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('floorPlan'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $floorPlan = $this->route('floorPlan');

        return [
            'booth_number' => [
                'required', 'string', 'max:50',
                Rule::unique('booths', 'booth_number')->where('floor_plan_id', $floorPlan->id),
            ],
            'organization_id' => [
                'nullable',
                Rule::exists('event_organization', 'organization_id')->where('event_id', $floorPlan->event_id),
            ],
            'x' => ['required', 'numeric', 'between:0,100'],
            'y' => ['required', 'numeric', 'between:0,100'],
            'width' => ['required', 'numeric', 'between:0.5,100'],
            'height' => ['required', 'numeric', 'between:0.5,100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
