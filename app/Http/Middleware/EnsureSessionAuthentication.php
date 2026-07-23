<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        abort_unless(
            $request->user() !== null && ($token === null || $token instanceof TransientToken),
            403
        );

        return $next($request);
    }
}
