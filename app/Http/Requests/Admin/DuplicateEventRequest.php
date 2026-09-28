<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DuplicateEventRequest extends FormRequest
{
    /**
     * You must be able to see the source event and be allowed to create events.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('event'))
            && $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:events,code'],
            'start_date' => ['nullable', 'date'],
            'include' => ['array'],
            'include.*' => ['in:team,organizations,tasks,floor_plans,abstracts'],
        ];
    }
}
