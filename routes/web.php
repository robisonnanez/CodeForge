<?php

use App\Http\Controllers\Admin\NavigationAdminController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Auth\ImpersonationController;
use App\Http\Controllers\CodeForge\PageController;
use App\Http\Controllers\GitHttpController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], 'git/{namespace}/{repository}.git/{path}', GitHttpController::class)
    ->where([
        'namespace' => '[a-z0-9]+(?:-[a-z0-9]+)*',
        'repository' => '[a-z0-9]+(?:-[a-z0-9]+)*',
        'path' => '.*',
    ])
    ->middleware('throttle:git')
    ->name('git.http');

Route::get('/healthz', HealthController::class)
    ->middleware('throttle:60,1')
    ->name('health');

Route::inertia('/', 'welcome', [
    'canRegister' => true,
])->name('home');

Route::get('/auth/error', static fn () => inertia('auth/error'))->name('auth.error');
Route::get('/auth/access', static fn () => inertia('auth/access'))->name('auth.access');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('codeforge', [PageController::class, 'dashboard'])->name('codeforge.dashboard');
    Route::get('codeforge/repositories', [PageController::class, 'repositoriesIndex'])->name('codeforge.repositories.index');
    Route::get('codeforge/ssh-keys', [PageController::class, 'sshKeys'])->name('codeforge.ssh-keys.index');
    Route::get('codeforge/repositories/new', [PageController::class, 'repositoriesCreate'])->name('codeforge.repositories.create');
    Route::get('codeforge/repositories/{repository}', [PageController::class, 'repositoriesShow'])->name('codeforge.repositories.show');
    Route::get('codeforge/repositories/{repository}/files', [PageController::class, 'repositoryFiles'])->name('codeforge.repositories.files');
    Route::get('codeforge/repositories/{repository}/commits', [PageController::class, 'repositoryCommits'])->name('codeforge.repositories.commits');
    Route::get('codeforge/repositories/{repository}/issues', [PageController::class, 'repositoryIssues'])->name('codeforge.repositories.issues');
    Route::get('codeforge/repositories/{repository}/issues/new', [PageController::class, 'repositoryIssuesCreate'])->name('codeforge.repositories.issues.create');
    if (config('codeforge.demo_features_enabled')) {
        Route::inertia('apps/calendar', 'apps/calendar')->name('apps.calendar');
        Route::inertia('apps/chat', 'apps/chat')->name('apps.chat');
        Route::inertia('apps/mail', 'apps/mail')->name('apps.mail');
        Route::inertia('apps/mail/inbox', 'apps/mail/inbox')->name('apps.mail.inbox');
        Route::inertia('apps/mail/compose', 'apps/mail/compose')->name('apps.mail.compose');
        Route::inertia('apps/mail/detail/{id}', 'apps/mail/detail')->name('apps.mail.detail');
        Route::inertia('apps/task-list', 'apps/task-list')->name('apps.task-list');
    }

    Route::middleware('role:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('roles-permissions', [RolePermissionController::class, 'index'])->name('roles-permissions.index');
        Route::post('roles-permissions/roles', [RolePermissionController::class, 'storeRole'])->name('roles-permissions.roles.store');
        Route::post('roles-permissions/{role}/permissions', [RolePermissionController::class, 'syncRolePermissions'])->name('roles-permissions.permissions.sync');

        Route::get('users-permissions', [UserPermissionController::class, 'index'])->name('users-permissions.index');
        Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
        Route::post('users', [UserAdminController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');
        Route::post('users-permissions/{user}/role', [UserPermissionController::class, 'syncUserRole'])->name('users-permissions.role.sync');
        Route::post('users-permissions/{user}/permissions', [UserPermissionController::class, 'syncUserPermissions'])->name('users-permissions.permissions.sync');
        Route::post('users-permissions/{user}/impersonate', [ImpersonationController::class, 'store'])->name('users-permissions.impersonate');

        Route::get('navigation-management', [NavigationAdminController::class, 'index'])->name('navigation.index');
        Route::post('navigation/modules', [NavigationAdminController::class, 'storeModule'])->name('navigation.modules.store');
        Route::put('navigation/modules/{modulo}', [NavigationAdminController::class, 'updateModule'])->name('navigation.modules.update');
        Route::delete('navigation/modules/{modulo}', [NavigationAdminController::class, 'destroyModule'])->name('navigation.modules.destroy');

        Route::post('navigation/menus', [NavigationAdminController::class, 'storeMenu'])->name('navigation.menus.store');
        Route::put('navigation/menus/{menu}', [NavigationAdminController::class, 'updateMenu'])->name('navigation.menus.update');
        Route::delete('navigation/menus/{menu}', [NavigationAdminController::class, 'destroyMenu'])->name('navigation.menus.destroy');
    });

    Route::post('impersonation/leave', [ImpersonationController::class, 'destroy'])->name('impersonation.leave');
});

require __DIR__.'/settings.php';
