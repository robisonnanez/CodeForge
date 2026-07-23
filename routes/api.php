<?php

use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\RepositoryController;
use App\Http\Controllers\Api\UserSshKeyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('web')->group(function (): void {
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('me', fn (Request $request) => response()->json([
            'user' => $request->user()?->only(['id', 'name', 'username', 'email']),
        ]))->name('me');
        Route::get('ssh-keys', [UserSshKeyController::class, 'index'])->name('ssh-keys.index');
        Route::post('ssh-keys', [UserSshKeyController::class, 'store'])->name('ssh-keys.store');
        Route::delete('ssh-keys/{sshKey}', [UserSshKeyController::class, 'destroy'])->name('ssh-keys.destroy');
        Route::get('tokens', [PersonalAccessTokenController::class, 'index'])->name('tokens.index');
        Route::post('tokens', [PersonalAccessTokenController::class, 'store'])
            ->middleware('throttle:security-sensitive')
            ->name('tokens.store');
        Route::delete('tokens/{token}', [PersonalAccessTokenController::class, 'destroy'])
            ->middleware('throttle:security-sensitive')
            ->name('tokens.destroy');

        Route::get('repositories', [RepositoryController::class, 'index'])->name('repositories.index');
        Route::post('repositories', [RepositoryController::class, 'store'])->name('repositories.store');
        Route::get('repositories/{repository}', [RepositoryController::class, 'show'])->name('repositories.show');
        Route::delete('repositories/{repository}', [RepositoryController::class, 'destroy'])->name('repositories.destroy');
        Route::get('repositories/{repository}/tree', [RepositoryController::class, 'files'])->name('repositories.tree');
        Route::get('repositories/{repository}/commits', [RepositoryController::class, 'commits'])->name('repositories.commits');
        Route::get('repositories/{repository}/branches', [RepositoryController::class, 'branches'])->name('repositories.branches');

        Route::get('repositories/{repository}/issues', [IssueController::class, 'index'])->name('repositories.issues.index');
        Route::post('repositories/{repository}/issues', [IssueController::class, 'store'])->name('repositories.issues.store');
        Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        Route::put('issues/{issue}', [IssueController::class, 'update'])->name('issues.update');
        Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');
    });
});
