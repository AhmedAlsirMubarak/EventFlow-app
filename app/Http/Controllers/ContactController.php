<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreContactRequest;
use App\Http\Requests\Admin\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

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
}
