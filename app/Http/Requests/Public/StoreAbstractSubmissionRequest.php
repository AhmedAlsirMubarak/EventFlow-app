<?php

namespace App\Http\Requests\Public;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbstractSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. A closed call must
     * reject the write outright, not merely hide the form.
     */
    public function authorize(): bool
    {
        return $this->route('abstractCall')->isAcceptingSubmissions();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $abstractCall = $this->route('abstractCall');

        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract_body' => ['required', 'string', 'max:10000'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'abstract_track_id' => [
                'nullable',
                Rule::exists('abstract_tracks', 'id')->where('abstract_call_id', $abstractCall->id),
            ],
            'speaker_name' => ['required', 'string', 'max:255'],
            'speaker_email' => ['required', 'email', 'max:255'],
            'speaker_phone' => ['nullable', 'string', 'max:50'],
            'speaker_bio' => ['nullable', 'string', 'max:5000'],
            'speaker_organization' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx'],
        ];
    }
}
