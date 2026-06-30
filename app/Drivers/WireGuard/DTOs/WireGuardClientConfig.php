<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard\DTOs;

final readonly class WireGuardClientConfig
{
    /**
     * @param string $configText  Complete WireGuard client configuration file
     *                            content ([Interface] + [Peer] sections).
     *                            Ready to be written to a .conf file or delivered
     *                            to the client. Terminated with a trailing newline.
     */
    public function __construct(
        public string $configText,
    ) {}
}
