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
            'status' => session('status'),
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
        $organization = Organization::create($request->validated());

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
        $organization->update($request->validated());

        return to_route('organizations.show', $organization)->with('status', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

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
