<?php

namespace App\Http\Controllers;

use App\Enums\CommentVisibility;
use App\Http\Requests\Admin\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Idea;
use App\Models\StakeholderRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function storeForIdea(StoreCommentRequest $request, Idea $idea): RedirectResponse
    {
        $this->storeComment($request, $idea);

        return back()->with('status', 'Comment added.');
    }

    public function storeForTask(StoreCommentRequest $request, Task $task): RedirectResponse
    {
        $this->storeComment($request, $task);

        return back()->with('status', 'Comment added.');
    }

    public function storeForRequest(StoreCommentRequest $request, StakeholderRequest $stakeholderRequest): RedirectResponse
    {
        $this->storeComment($request, $stakeholderRequest);

        return back()->with('status', 'Comment added.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', 'Comment removed.');
    }

    private function storeComment(StoreCommentRequest $request, Model $commentable): void
    {
        $user = $request->user();

        $commentable->comments()->create([
            'user_id' => $user->isExternal() ? null : $user->id,
            'contact_id' => $user->isExternal() ? $user->contact->id : null,
            'visibility' => $user->isExternal()
                ? CommentVisibility::External
                : CommentVisibility::from($request->validated('visibility') ?? CommentVisibility::Internal->value),
            'body' => $request->validated('body'),
        ]);
    }
}
