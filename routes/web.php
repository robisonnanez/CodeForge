<?php

use App\Http\Controllers\Admin\NavigationAdminController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Auth\PasskeyController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome', [
    'canRegister' => false,
])->name('home');

Route::any('register', static function () {
    abort(404);
})->name('register.blocked');

Route::get('passkeys/authentication-options', [PasskeyController::class, 'authenticationOptions'])->name('passkeys.authentication_options');
Route::post('passkeys/authenticate', [PasskeyController::class, 'authenticate'])->name('passkeys.login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('apps/calendar', 'apps/calendar')->name('apps.calendar');
    Route::inertia('apps/chat', 'apps/chat')->name('apps.chat');
    Route::inertia('apps/mail', 'apps/mail')->name('apps.mail');
    Route::inertia('apps/mail/inbox', 'apps/mail/inbox')->name('apps.mail.inbox');
    Route::inertia('apps/mail/compose', 'apps/mail/compose')->name('apps.mail.compose');
    Route::inertia('apps/mail/detail/{id}', 'apps/mail/detail')->name('apps.mail.detail');
    Route::inertia('apps/task-list', 'apps/task-list')->name('apps.task-list');

    Route::get('settings/passkeys/registration-options', [PasskeyController::class, 'registerOptions'])->name('settings.passkeys.options');
    Route::post('settings/passkeys', [PasskeyController::class, 'store'])->name('settings.passkeys.store');
    Route::delete('settings/passkeys/{passkey}', [PasskeyController::class, 'destroy'])->name('settings.passkeys.destroy');

    Route::middleware('role:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('roles-permissions', [RolePermissionController::class, 'index'])->name('roles-permissions.index');
        Route::post('roles-permissions/roles', [RolePermissionController::class, 'storeRole'])->name('roles-permissions.roles.store');
        Route::post('roles-permissions/{role}/permissions', [RolePermissionController::class, 'syncRolePermissions'])->name('roles-permissions.permissions.sync');

        Route::get('users-permissions', [UserPermissionController::class, 'index'])->name('users-permissions.index');
        Route::post('users-permissions/{user}/role', [UserPermissionController::class, 'syncUserRole'])->name('users-permissions.role.sync');
        Route::post('users-permissions/{user}/permissions', [UserPermissionController::class, 'syncUserPermissions'])->name('users-permissions.permissions.sync');

        Route::get('navigation-management', [NavigationAdminController::class, 'index'])->name('navigation.index');
        Route::post('navigation/modules', [NavigationAdminController::class, 'storeModule'])->name('navigation.modules.store');
        Route::put('navigation/modules/{modulo}', [NavigationAdminController::class, 'updateModule'])->name('navigation.modules.update');
        Route::delete('navigation/modules/{modulo}', [NavigationAdminController::class, 'destroyModule'])->name('navigation.modules.destroy');

        Route::post('navigation/menus', [NavigationAdminController::class, 'storeMenu'])->name('navigation.menus.store');
        Route::put('navigation/menus/{menu}', [NavigationAdminController::class, 'updateMenu'])->name('navigation.menus.update');
        Route::delete('navigation/menus/{menu}', [NavigationAdminController::class, 'destroyMenu'])->name('navigation.menus.destroy');
    });
});

require __DIR__.'/settings.php';
