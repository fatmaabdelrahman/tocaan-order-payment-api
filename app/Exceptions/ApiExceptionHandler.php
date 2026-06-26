<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * API exception configuration. We intentionally rely on Laravel's default JSON
 * rendering for validation (422), auth (401/403), HttpException (incl. our 409
 * BusinessRuleException), and 500s — and only encode the two deltas the framework
 * doesn't give us out of the box.
 */
class ApiExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        // Delta 1: never return HTML on an API route — force JSON even when the
        // client omits the `Accept: application/json` header.
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );

        // Delta 2: a friendly, non-leaking 404 message (the default can be empty
        // or surface the missing model class).
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json(['message' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        });
    }
}
