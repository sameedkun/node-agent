<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Http\Request;

/**
 * Contract for authenticating requests from the Control Plane.
 *
 * The current implementation uses a shared-secret header pair.
 * Upgrading to HMAC request signing requires only a new class that
 * implements this interface and a single binding change in
 * AppServiceProvider — no middleware, controller, or service changes.
 */
interface ControlPlaneAuthentication
{
    /**
     * Returns true if the request carries valid Control Plane credentials,
     * false otherwise.
     *
     * Implementations must never throw on invalid input; they must return
     * false. All comparisons must run in constant time to prevent
     * timing side-channels.
     */
    public function verify(Request $request): bool;
}
