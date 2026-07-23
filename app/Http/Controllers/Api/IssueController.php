<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;

class IssueController extends Controller
{
    public function index(Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json([
            'issues' => $repository->issues()->with('author:id,name,email')->latest()->get(),
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

        return response()->json([
            'message' => 'Issue created successfully.',
            'issue' => $issue->load('author:id,name,email'),
        ], 201);
    }

    public function show(Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        return response()->json([
            'issue' => $issue->load(['author:id,name,email', 'repository:id,name,slug,visibility,user_id']),
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): JsonResponse
    {
        $this->authorize('update', $issue);

        $issue->fill($request->validated());
        $issue->save();

        return response()->json([
            'message' => 'Issue updated successfully.',
            'issue' => $issue->load('author:id,name,email'),
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
