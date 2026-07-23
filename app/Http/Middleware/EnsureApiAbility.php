<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        abort_unless($user !== null && ($token === null || $user->tokenCan($ability)), 403);

        return $next($request);
    }
}
