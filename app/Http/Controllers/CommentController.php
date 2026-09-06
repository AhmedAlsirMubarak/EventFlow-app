<?php

namespace App\Http\Controllers;

use App\Enums\CommentVisibility;
use App\Http\Requests\Admin\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Idea;
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

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', 'Comment removed.');
    }

    private function storeComment(StoreCommentRequest $request, Model $commentable): void
    {
        $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'visibility' => CommentVisibility::Internal,
            'body' => $request->validated('body'),
        ]);
    }
}
