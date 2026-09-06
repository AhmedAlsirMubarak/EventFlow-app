<?php

use App\Enums\Role;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskChecklistItemController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:'.Role::SuperAdministrator->value.'|'.Role::Management->value])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('organizations', OrganizationController::class);
    Route::post('organizations/{organization}/contacts', [ContactController::class, 'store'])->name('organizations.contacts.store');
    Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    Route::resource('events', EventController::class);
    Route::post('events/{event}/team', [EventController::class, 'attachUser'])->name('events.team.store');
    Route::delete('events/{event}/team/{user}', [EventController::class, 'detachUser'])->name('events.team.destroy');
    Route::post('events/{event}/organizations', [EventController::class, 'attachOrganization'])->name('events.organizations.store');
    Route::delete('events/{event}/organizations/{organization}', [EventController::class, 'detachOrganization'])->name('events.organizations.destroy');

    Route::post('events/{event}/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::put('ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');

    Route::post('events/{event}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::post('tasks/{task}/checklist-items', [TaskChecklistItemController::class, 'store'])->name('checklist-items.store');
    Route::patch('checklist-items/{checklistItem}', [TaskChecklistItemController::class, 'update'])->name('checklist-items.update');
    Route::delete('checklist-items/{checklistItem}', [TaskChecklistItemController::class, 'destroy'])->name('checklist-items.destroy');
});

require __DIR__.'/auth.php';
