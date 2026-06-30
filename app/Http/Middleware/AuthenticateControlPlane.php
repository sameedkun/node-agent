<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\ControlPlaneAuthentication;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthenticateControlPlane
{
    public function __construct(
        private readonly ControlPlaneAuthentication $auth,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->auth->verify($request)) {
            return new JsonResponse(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
