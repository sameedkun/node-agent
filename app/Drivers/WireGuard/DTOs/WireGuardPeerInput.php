<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard\DTOs;

final readonly class WireGuardPeerInput
{
    /**
     * @param string      $clientPrivateKey     Client's WireGuard private key.
     * @param string      $clientPublicKey      Client's WireGuard public key.
     * @param string      $presharedKey         Preshared key shared by both peers.
     * @param string      $clientAddress        Client's VPN interface address with
     *                                          subnet mask (e.g. "10.8.0.2/24").
     *                                          Written to [Interface] Address.
     * @param string      $serverPeerAllowedIps Source IPs the server will accept
     *                                          from this peer (e.g. "10.8.0.2/32").
     *                                          Written to server [Peer] AllowedIPs.
     * @param string      $serverPublicKey      Server's WireGuard public key.
     * @param string      $serverEndpoint       Server's public endpoint host:port
     *                                          (e.g. "203.0.113.1:51820").
     * @param string      $clientAllowedIps     Traffic the client should route
     *                                          through the tunnel
     *                                          (e.g. "0.0.0.0/0, ::/0").
     *                                          Written to client [Peer] AllowedIPs.
     * @param string|null $dns                  Comma-separated DNS servers for the
     *                                          client interface (e.g. "1.1.1.1").
     *                                          Omitted from config when null.
     * @param int|null    $persistentKeepalive  Keepalive interval in seconds.
     *                                          Omitted from config when null.
     */
    public function __construct(
        public string $clientPrivateKey,
        public string $clientPublicKey,
        public string $presharedKey,
        public string $clientAddress,
        public string $serverPeerAllowedIps,
        public string $serverPublicKey,
        public string $serverEndpoint,
        public string $clientAllowedIps,
        public ?string $dns,
        public ?int $persistentKeepalive,
    ) {}
}
