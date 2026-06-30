<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard;

use App\Drivers\WireGuard\DTOs\WireGuardClientConfig;
use App\Drivers\WireGuard\DTOs\WireGuardPeerInput;
use App\Drivers\WireGuard\DTOs\WireGuardServerConfig;

final class WireGuardConfigGenerator
{
    /**
     * Generates the [Peer] block that must be appended to the server's
     * WireGuard configuration file for this client.
     *
     * Example output:
     *
     *   [Peer]
     *   PublicKey = <client_public_key>
     *   PresharedKey = <preshared_key>
     *   AllowedIPs = 10.8.0.2/32
     */
    public function generateServerPeerBlock(WireGuardPeerInput $input): WireGuardServerConfig
    {
        $lines = [
            '[Peer]',
            "PublicKey = {$input->clientPublicKey}",
            "PresharedKey = {$input->presharedKey}",
            "AllowedIPs = {$input->serverPeerAllowedIps}",
        ];

        return new WireGuardServerConfig(
            peerBlock: implode("\n", $lines) . "\n",
        );
    }

    /**
     * Generates a complete WireGuard client configuration file.
     *
     * Example output:
     *
     *   [Interface]
     *   PrivateKey = <client_private_key>
     *   Address = 10.8.0.2/24
     *   DNS = 1.1.1.1, 8.8.8.8
     *
     *   [Peer]
     *   PublicKey = <server_public_key>
     *   PresharedKey = <preshared_key>
     *   Endpoint = 203.0.113.1:51820
     *   AllowedIPs = 0.0.0.0/0, ::/0
     *   PersistentKeepalive = 25
     */
    public function generateClientConfig(WireGuardPeerInput $input): WireGuardClientConfig
    {
        $lines = [
            '[Interface]',
            "PrivateKey = {$input->clientPrivateKey}",
            "Address = {$input->clientAddress}",
        ];

        if ($input->dns !== null) {
            $lines[] = "DNS = {$input->dns}";
        }

        $lines[] = '';
        $lines[] = '[Peer]';
        $lines[] = "PublicKey = {$input->serverPublicKey}";
        $lines[] = "PresharedKey = {$input->presharedKey}";
        $lines[] = "Endpoint = {$input->serverEndpoint}";
        $lines[] = "AllowedIPs = {$input->clientAllowedIps}";

        if ($input->persistentKeepalive !== null) {
            $lines[] = "PersistentKeepalive = {$input->persistentKeepalive}";
        }

        return new WireGuardClientConfig(
            configText: implode("\n", $lines) . "\n",
        );
    }
}
