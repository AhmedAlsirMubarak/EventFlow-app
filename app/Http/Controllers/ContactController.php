<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\UserType;
use App\Http\Requests\Admin\StoreContactRequest;
use App\Http\Requests\Admin\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\PortalAccessGrantedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request, Organization $organization): RedirectResponse
    {
        $organization->contacts()->create($request->validated());

        return to_route('organizations.show', $organization)->with('status', 'Contact added.');
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        return to_route('organizations.show', $contact->organization_id)->with('status', 'Contact updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        Gate::authorize('delete', $contact);

        $organizationId = $contact->organization_id;
        $contact->delete();

        return to_route('organizations.show', $organizationId)->with('status', 'Contact removed.');
    }

    public function grantPortalAccess(Contact $contact): RedirectResponse
    {
        Gate::authorize('update', $contact);

        if ($contact->user_id) {
            return back()->with('status', 'This contact already has portal access.');
        }

        if (! $contact->email) {
            return back()->with('status', 'Add an email address for this contact before granting portal access.');
        }

        if (User::where('email', $contact->email)->exists()) {
            return back()->with('status', 'A user account with this email already exists.');
        }

        $user = User::create([
            'name' => $contact->full_name,
            'email' => $contact->email,
            'password' => Hash::make(Str::random(32)),
            'user_type' => UserType::External,
        ]);
        $user->assignRole(Role::ExternalStakeholder->value);

        $contact->update(['user_id' => $user->id]);

        $token = Password::broker()->createToken($user);
        $resetLink = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $contact->notify(new PortalAccessGrantedNotification($resetLink));

        return back()->with([
            'status' => 'Portal access granted.',
            'resetLink' => $resetLink,
        ]);
    }
}
