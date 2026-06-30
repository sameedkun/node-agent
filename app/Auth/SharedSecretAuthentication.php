<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Http\Request;

/**
 * Authenticates Control Plane requests via two HTTP headers:
 *
 *   X-Node-UUID    — must match the configured NODE_UUID
 *   X-Node-Secret  — must match the configured NODE_SECRET
 *
 * Both comparisons always run regardless of whether the first fails.
 * Bitwise & (not logical &&) is used so neither branch can short-circuit,
 * preventing timing side-channels that would distinguish a wrong UUID
 * from a wrong secret.
 */
final class SharedSecretAuthentication implements ControlPlaneAuthentication
{
    public function __construct(
        private readonly string $nodeUuid,
        private readonly string $nodeSecret,
    ) {}

    public function verify(Request $request): bool
    {
        $uuid   = (string) ($request->header('X-Node-UUID') ?? '');
        $secret = (string) ($request->header('X-Node-Secret') ?? '');

        // Bitwise & ensures both hash_equals() calls always execute.
        $result = hash_equals($this->nodeUuid, $uuid)
                & hash_equals($this->nodeSecret, $secret);

        return (bool) $result;
    }
}
