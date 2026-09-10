<?php

namespace App\Http\Controllers;

use App\Enums\CommentVisibility;
use App\Http\Requests\Admin\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Idea;
use App\Models\StakeholderRequest;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use App\Notifications\StakeholderRequestCommentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

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

        $comment = $commentable->comments()->create([
            'user_id' => $user->isExternal() ? null : $user->id,
            'contact_id' => $user->isExternal() ? $user->contact->id : null,
            'visibility' => $user->isExternal()
                ? CommentVisibility::External
                : CommentVisibility::from($request->validated('visibility') ?? CommentVisibility::Internal->value),
            'body' => $request->validated('body'),
        ]);

        $this->notifyAboutComment($comment, $commentable, $user);
    }

    /**
     * Staff previously had no way to learn about a new comment besides re-checking the
     * page manually — this notifies whoever is already engaged with the commentable:
     * its creator and (for tasks/requests) its assignee, skipping the person who just
     * posted. A stakeholder-visible comment on a request also emails the contact.
     */
    private function notifyAboutComment(Comment $comment, Model $commentable, User $user): void
    {
        if (! $user->isExternal()
            && $commentable instanceof StakeholderRequest
            && $comment->visibility === CommentVisibility::External
            && $commentable->contact) {
            Notification::route('mail', $commentable->contact->email)
                ->notify(new StakeholderRequestCommentNotification($comment));
        }

        $recipients = collect([$commentable->creator, $commentable->assignee ?? null])
            ->filter()
            ->unique('id')
            ->reject(fn (User $recipient) => ! $user->isExternal() && $recipient->id === $user->id);

        Notification::send($recipients, new CommentPostedNotification($comment));
    }
}
