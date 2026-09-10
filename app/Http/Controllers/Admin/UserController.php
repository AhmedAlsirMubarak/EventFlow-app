<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->where('user_type', UserType::Internal)
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'availableRoles' => Role::assignableBy($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'user_type' => UserType::Internal,
        ]);

        $user->syncRoles($request->validated('roles', []));

        return to_route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only(['id', 'name', 'email']),
            'assignedRoles' => $user->getRoleNames(),
            'availableRoles' => Role::assignableBy($request->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->validated('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        $user->syncRoles($request->validated('roles', []));

        return to_route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return to_route('admin.users.index')->with('status', 'User removed.');
    }
}
