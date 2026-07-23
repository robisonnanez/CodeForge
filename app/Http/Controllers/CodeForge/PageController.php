<?php

namespace App\Http\Controllers\CodeForge;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('codeforge/dashboard');
    }

    public function repositoriesIndex(): Response
    {
        return Inertia::render('codeforge/repositories/index');
    }

    public function sshKeys(): Response
    {
        return Inertia::render('codeforge/ssh-keys/index');
    }

    public function repositoriesCreate(): Response
    {
        return Inertia::render('codeforge/repositories/new');
    }

    public function repositoriesShow(Repository $repository): Response
    {
        $this->authorize('view', $repository);

        return Inertia::render('codeforge/repositories/show', [
            'repositoryId' => $repository->id,
        ]);
    }

    public function repositoryFiles(Repository $repository): Response
    {
        $this->authorize('view', $repository);

        return Inertia::render('codeforge/repositories/files', [
            'repositoryId' => $repository->id,
        ]);
    }

    public function repositoryCommits(Repository $repository): Response
    {
        $this->authorize('view', $repository);

        return Inertia::render('codeforge/repositories/commits', [
            'repositoryId' => $repository->id,
        ]);
    }

    public function repositoryIssues(Repository $repository): Response
    {
        $this->authorize('view', $repository);

        return Inertia::render('codeforge/repositories/issues', [
            'repositoryId' => $repository->id,
        ]);
    }

    public function repositoryIssuesCreate(Repository $repository): Response
    {
        $this->authorize('view', $repository);

        return Inertia::render('codeforge/repositories/issues-new', [
            'repositoryId' => $repository->id,
        ]);
    }
}
