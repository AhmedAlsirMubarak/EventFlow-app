<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationCategory;
use App\Enums\PreferredChannel;
use App\Enums\Role;
use App\Enums\UserType;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Organization::class);

        return Inertia::render('Organizations/Index', [
            'organizations' => Organization::query()
                ->withCount('contacts')
                ->with('accountManager:id,name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Organization::class);

        return Inertia::render('Organizations/Create', [
            'categories' => array_column(OrganizationCategory::cases(), 'value'),
            'accountManagers' => $this->accountManagerOptions(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = Organization::create($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            $organization->update(['logo_path' => $request->file('logo')->store('organization-logos', 'public')]);
        }

        return to_route('organizations.show', $organization)->with('status', 'Organization created.');
    }

    public function show(Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        return Inertia::render('Organizations/Show', [
            'organization' => $organization->load('accountManager:id,name'),
            'contacts' => $organization->contacts()->orderBy('full_name')->get(),
            'preferredChannels' => array_column(PreferredChannel::cases(), 'value'),
            'status' => session('status'),
            'resetLink' => session('resetLink'),
            'importErrors' => session('importErrors', []),
        ]);
    }

    public function edit(Organization $organization): Response
    {
        Gate::authorize('update', $organization);

        return Inertia::render('Organizations/Edit', [
            'organization' => $organization,
            'categories' => array_column(OrganizationCategory::cases(), 'value'),
            'accountManagers' => $this->accountManagerOptions(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('organization-logos', 'public');
        } elseif ($request->boolean('remove_logo') && $organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
            $data['logo_path'] = null;
        }

        $organization->update($data);

        return to_route('organizations.show', $organization)->with('status', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        if ($organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
        }

        $organization->delete();

        return to_route('organizations.index')->with('status', 'Organization removed.');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function accountManagerOptions(): array
    {
        return User::query()
            ->where('user_type', UserType::Internal)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                Role::AccountManager->value,
                Role::SalesSponsorshipTeam->value,
            ]))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }
}
