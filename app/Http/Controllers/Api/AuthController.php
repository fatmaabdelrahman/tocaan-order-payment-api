<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    /**
     * Register a new user and return a JWT.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = $this->guard()->login($user);

        return $this->tokenResponse($token, $user, 201);
    }

    /**
     * Authenticate a user and return a JWT.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt($request->validated());

        if (! $token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->tokenResponse($token, $this->guard()->user());
    }

    /**
     * Return the authenticated user.
     */
    public function me(): JsonResponse
    {
        return response()->json(['data' => $this->guard()->user()]);
    }

    /**
     * Invalidate the current token.
     */
    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    /**
     * Issue a fresh token for the current one.
     */
    public function refresh(): JsonResponse
    {
        $token = $this->guard()->refresh();

        return $this->tokenResponse($token, $this->guard()->user());
    }

    /**
     * Build the standard token response envelope.
     */
    protected function tokenResponse(string $token, ?Authenticatable $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'user' => $user,
        ], $status);
    }

    /**
     * The JWT auth guard, typed so the package-specific methods
     * (login/attempt/refresh/factory) are statically known.
     */
    protected function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        return $guard;
    }
}
