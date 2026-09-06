<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_users' => User::query()->count(),
                'internal_users' => User::query()->where('user_type', UserType::Internal)->count(),
                'external_users' => User::query()->where('user_type', UserType::External)->count(),
                'roles_configured' => Role::query()->count(),
            ],
        ]);
    }
}
