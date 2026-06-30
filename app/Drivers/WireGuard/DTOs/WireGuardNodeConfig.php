<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard\DTOs;

/**
 * Immutable snapshot of the server-side WireGuard configuration.
 *
 * Populated from config/wireguard.php and bound as a singleton in
 * AppServiceProvider so all WireGuard services share one consistent view.
 */
final readonly class WireGuardNodeConfig
{
    /**
     * @param string      $serverPublicKey    Server's base64-encoded WireGuard
     *                                        public key. Written into every
     *                                        generated client config file.
     * @param string      $serverEndpoint     Public endpoint the server listens
     *                                        on, e.g. "203.0.113.1:51820".
     * @param string      $subnet             VPN CIDR block, e.g. "10.8.0.0/24".
     *                                        Host .1 is reserved for the server;
     *                                        clients start at .2.
     * @param string      $clientAllowedIps   Routes to tunnel through the VPN,
     *                                        written to the client [Peer] block.
     * @param string|null $dns                Comma-separated DNS servers for the
     *                                        client [Interface] block, or null to
     *                                        omit the line entirely.
     * @param int|null    $persistentKeepalive Keepalive in seconds, or null to
     *                                        omit the line entirely.
     */
    public function __construct(
        public string $serverPublicKey,
        public string $serverEndpoint,
        public string $subnet,
        public string $clientAllowedIps,
        public ?string $dns,
        public ?int $persistentKeepalive,
    ) {}
}
