<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        $issues = $repository->issues()
            ->with('author:id,name,username')
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'issues' => $issues->items(),
            'meta' => [
                'current_page' => $issues->currentPage(),
                'last_page' => $issues->lastPage(),
                'per_page' => $issues->perPage(),
                'total' => $issues->total(),
            ],
        ]);
    }

    public function store(StoreIssueRequest $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        $issue = $repository->issues()->create([
            'user_id' => $request->user()->id,
            'title' => $request->string('title')->toString(),
            'description' => $request->string('description')->toString() ?: null,
            'status' => 'open',
        ]);

        activity('codeforge')
            ->causedBy($request->user())
            ->performedOn($issue)
            ->withProperties(['repository_id' => $repository->id, 'result' => 'allowed'])
            ->log('issue.created');

        return response()->json([
            'message' => 'Issue created successfully.',
            'issue' => $issue->load('author:id,name,username'),
        ], 201);
    }

    public function show(Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        return response()->json([
            'issue' => $issue->load(['author:id,name,username', 'repository:id,name,slug,visibility,user_id']),
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): JsonResponse
    {
        $this->authorize('update', $issue);

        $issue->fill($request->validated());
        $issue->save();

        activity('codeforge')
            ->causedBy($request->user())
            ->performedOn($issue)
            ->withProperties(['repository_id' => $issue->repository_id, 'changes' => $issue->getChanges(), 'result' => 'allowed'])
            ->log('issue.updated');

        return response()->json([
            'message' => 'Issue updated successfully.',
            'issue' => $issue->load('author:id,name,username'),
        ]);
    }

    public function destroy(Issue $issue): JsonResponse
    {
        $this->authorize('delete', $issue);
        $issue->delete();

        return response()->json([
            'message' => 'Issue deleted successfully.',
        ]);
    }
}
