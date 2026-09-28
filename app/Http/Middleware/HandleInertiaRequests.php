<?php

namespace App\Http\Middleware;

use App\Models\CommitteeMember;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames() ?? [],
                'contactId' => $user?->contact?->id,
            ],
            'unreadNotificationsCount' => $user?->unreadNotifications()->count() ?? 0,
            'hasCommitteeAssignments' => $user ? CommitteeMember::query()->where('user_id', $user->id)->exists() : false,
            'flash' => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
                // Restore URL for the record just deleted, rendered as an "Undo" button.
                'undo' => $request->session()->get('undo'),
            ],
        ];
    }
}
