<?php

use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\RepositoryController;
use App\Http\Controllers\Api\RepositoryMemberController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\UserSshKeyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('web')->group(function (): void {
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('me', fn (Request $request) => response()->json([
            'user' => $request->user()?->only(['id', 'name', 'username', 'email']),
        ]))->name('me');
        Route::get('sessions', [SessionController::class, 'index'])->middleware('session.auth')->name('sessions.index');
        Route::delete('sessions/{session}', [SessionController::class, 'destroy'])
            ->middleware(['session.auth', 'throttle:security-sensitive'])
            ->name('sessions.destroy');
        Route::get('ssh-keys', [UserSshKeyController::class, 'index'])->middleware('session.auth')->name('ssh-keys.index');
        Route::post('ssh-keys', [UserSshKeyController::class, 'store'])->middleware('session.auth')->name('ssh-keys.store');
        Route::delete('ssh-keys/{sshKey}', [UserSshKeyController::class, 'destroy'])->middleware('session.auth')->name('ssh-keys.destroy');
        Route::get('tokens', [PersonalAccessTokenController::class, 'index'])->middleware('session.auth')->name('tokens.index');
        Route::post('tokens', [PersonalAccessTokenController::class, 'store'])
            ->middleware(['session.auth', 'throttle:security-sensitive'])
            ->name('tokens.store');
        Route::delete('tokens/{token}', [PersonalAccessTokenController::class, 'destroy'])
            ->middleware(['session.auth', 'throttle:security-sensitive'])
            ->name('tokens.destroy');

        Route::get('repositories', [RepositoryController::class, 'index'])->middleware('api.ability:repo:read')->name('repositories.index');
        Route::post('repositories', [RepositoryController::class, 'store'])->middleware('api.ability:repo:write')->name('repositories.store');
        Route::get('repositories/{repository}', [RepositoryController::class, 'show'])->middleware('api.ability:repo:read')->name('repositories.show');
        Route::delete('repositories/{repository}', [RepositoryController::class, 'destroy'])->middleware('api.ability:repo:write')->name('repositories.destroy');
        Route::get('repositories/{repository}/tree', [RepositoryController::class, 'tree'])->middleware('api.ability:repo:read')->name('repositories.tree');
        Route::get('repositories/{repository}/blob', [RepositoryController::class, 'blob'])->middleware('api.ability:repo:read')->name('repositories.blob');
        Route::get('repositories/{repository}/compare', [RepositoryController::class, 'compare'])->middleware('api.ability:repo:read')->name('repositories.compare');
        Route::get('repositories/{repository}/archive', [RepositoryController::class, 'archive'])
            ->middleware(['api.ability:repo:read', 'throttle:security-sensitive'])
            ->name('repositories.archive');
        Route::get('repositories/{repository}/commits', [RepositoryController::class, 'commits'])->middleware('api.ability:repo:read')->name('repositories.commits');
        Route::get('repositories/{repository}/branches', [RepositoryController::class, 'branches'])->middleware('api.ability:repo:read')->name('repositories.branches');
        Route::get('repositories/{repository}/tags', [RepositoryController::class, 'tags'])->middleware('api.ability:repo:read')->name('repositories.tags');
        Route::get('repositories/{repository}/members', [RepositoryMemberController::class, 'index'])->middleware('session.auth')->name('repositories.members.index');
        Route::post('repositories/{repository}/members', [RepositoryMemberController::class, 'store'])
            ->middleware(['session.auth', 'throttle:security-sensitive'])
            ->name('repositories.members.store');
        Route::put('repositories/{repository}/members/{member}', [RepositoryMemberController::class, 'update'])
            ->middleware(['session.auth', 'throttle:security-sensitive'])
            ->name('repositories.members.update');
        Route::delete('repositories/{repository}/members/{member}', [RepositoryMemberController::class, 'destroy'])
            ->middleware(['session.auth', 'throttle:security-sensitive'])
            ->name('repositories.members.destroy');

        Route::get('repositories/{repository}/issues', [IssueController::class, 'index'])->middleware('api.ability:repo:read')->name('repositories.issues.index');
        Route::post('repositories/{repository}/issues', [IssueController::class, 'store'])->middleware('api.ability:repo:write')->name('repositories.issues.store');
        Route::get('issues/{issue}', [IssueController::class, 'show'])->middleware('api.ability:repo:read')->name('issues.show');
        Route::put('issues/{issue}', [IssueController::class, 'update'])->middleware('api.ability:repo:write')->name('issues.update');
        Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->middleware('api.ability:repo:write')->name('issues.destroy');
    });
});
