<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(config('session.driver') === 'database', 501);
        $currentId = $request->session()->getId();

        return response()->json([
            'sessions' => DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->orderByDesc('last_activity')
                ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
                ->map(fn (object $session): array => [
                    'id' => $this->opaqueId($session->id),
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => $session->last_activity,
                    'current' => hash_equals($currentId, $session->id),
                ])
                ->values(),
        ]);
    }

    public function destroy(Request $request, string $session): JsonResponse
    {
        abort_unless(config('session.driver') === 'database', 501);
        $currentId = $request->session()->getId();
        $candidate = DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->get(['id'])
            ->first(fn (object $row): bool => hash_equals($session, $this->opaqueId($row->id)));

        abort_if($candidate === null || hash_equals($currentId, $candidate->id), 422);

        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', $candidate->id)
            ->delete();

        activity('security')
            ->causedBy($request->user())
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('authentication.session.revoked');

        return response()->json(['message' => 'Session revoked.']);
    }

    private function opaqueId(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }
}
