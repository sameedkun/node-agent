<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard\DTOs;

final readonly class WireGuardServerConfig
{
    /**
     * @param string $peerBlock  A complete [Peer] block ready to be appended to
     *                           the server's wg0.conf (or equivalent).
     *                           Terminated with a trailing newline.
     */
    public function __construct(
        public string $peerBlock,
    ) {}
}
