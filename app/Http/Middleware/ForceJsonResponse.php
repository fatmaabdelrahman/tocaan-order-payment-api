<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees every API request is treated as JSON, even when the client forgets
 * the `Accept: application/json` header. This makes $request->expectsJson() true
 * throughout the stack, so framework components (e.g. the Authenticate middleware)
 * return JSON 401s instead of attempting an HTML redirect to a non-existent login route.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
