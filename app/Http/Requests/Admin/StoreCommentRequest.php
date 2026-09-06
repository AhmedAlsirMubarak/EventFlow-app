<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommentVisibility;
use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $commentable = $this->route('idea') ?? $this->route('task') ?? $this->route('stakeholderRequest');

        return $this->user()->can('create', [Comment::class, $commentable]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['sometimes', new Enum(CommentVisibility::class)],
        ];
    }
}
