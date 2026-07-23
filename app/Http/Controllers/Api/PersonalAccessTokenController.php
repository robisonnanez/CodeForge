<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'tokens' => $request->user()->tokens()
                ->latest()
                ->get()
                ->map(fn (PersonalAccessToken $token): array => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities ?? [],
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'created_at' => $token->created_at,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'abilities' => ['required', 'array', 'min:1', 'max:2'],
            'abilities.*' => ['required', Rule::in(['repo:read', 'repo:write'])],
            'expires_at' => ['required', 'date', 'after:now', 'before_or_equal:'.now()->addYear()->toIso8601String()],
        ]);

        $abilities = array_values(array_unique($validated['abilities']));

        if (in_array('repo:write', $abilities, true) && ! in_array('repo:read', $abilities, true)) {
            $abilities[] = 'repo:read';
        }

        $token = $request->user()->createToken(
            $validated['name'],
            $abilities,
            new \DateTimeImmutable($validated['expires_at'])
        );

        activity('security')
            ->causedBy($request->user())
            ->withProperties([
                'token_id' => $token->accessToken->id,
                'abilities' => $token->accessToken->abilities,
                'expires_at' => $token->accessToken->expires_at,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('personal_access_token.created');

        return response()->json([
            'message' => 'Personal access token created. Copy it now; it will not be shown again.',
            'token' => $token->plainTextToken,
            'metadata' => [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'abilities' => $token->accessToken->abilities,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ], 201);
    }

    public function destroy(Request $request, PersonalAccessToken $token): JsonResponse
    {
        abort_unless(
            $token->tokenable_type === $request->user()::class
            && $token->tokenable_id === $request->user()->getKey(),
            404
        );

        $tokenId = $token->id;
        $token->delete();

        activity('security')
            ->causedBy($request->user())
            ->withProperties([
                'token_id' => $tokenId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('personal_access_token.revoked');

        return response()->json(['message' => 'Personal access token revoked.']);
    }
}
