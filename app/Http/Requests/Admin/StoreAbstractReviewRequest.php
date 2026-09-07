<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewRecommendation;
use App\Models\AbstractReview;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbstractReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [AbstractReview::class, $this->route('abstractSubmission')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['required', Rule::in(array_column(ReviewRecommendation::cases(), 'value'))],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
