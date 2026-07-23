<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSshKey;
use App\Services\SshKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserSshKeyController extends Controller
{
    public function __construct(
        private readonly SshKeyService $sshKeyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'ssh_keys' => $request->user()?->sshKeys()
                ->latest()
                ->get()
                ->map(fn (UserSshKey $key) => $this->sshKeyService->metadata($key))
                ->values() ?? [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'public_key' => ['required', 'string', 'min:32', 'max:4096', Rule::unique('user_ssh_keys', 'public_key')],
        ]);

        $keyData = $this->sshKeyService->inspectPublicKey($validated['public_key']);

        $sshKey = $request->user()->sshKeys()->create([
            'name' => $validated['name'],
            ...$keyData,
        ]);

        activity('security')
            ->causedBy($request->user())
            ->withProperties([
                'ssh_key_id' => $sshKey->id,
                'fingerprint' => $sshKey->fingerprint,
                'algorithm' => $sshKey->algorithm,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('ssh_key.created');

        return response()->json([
            'message' => 'SSH key added successfully.',
            'ssh_key' => $this->sshKeyService->metadata($sshKey),
        ], 201);
    }

    public function destroy(Request $request, UserSshKey $sshKey): JsonResponse
    {
        abort_unless($sshKey->user_id === $request->user()?->id, 403);

        $sshKey->forceFill(['revoked_at' => now()])->save();

        activity('security')
            ->causedBy($request->user())
            ->withProperties([
                'ssh_key_id' => $sshKey->id,
                'fingerprint' => $sshKey->fingerprint,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('ssh_key.revoked');

        return response()->json([
            'message' => 'SSH key revoked successfully.',
        ]);
    }
}
