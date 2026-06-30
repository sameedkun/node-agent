<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard;

use App\Contracts\ProtocolDriver;
use App\DTOs\ConfigResult;
use App\DTOs\CreateConfigData;
use App\Drivers\WireGuard\Contracts\WireGuardConfigRepositoryInterface;
use App\Drivers\WireGuard\DTOs\WireGuardNodeConfig;
use App\Drivers\WireGuard\DTOs\WireGuardPeerInput;
use App\Drivers\WireGuard\Exceptions\WireGuardException;
use App\Enums\Protocol;

final class WireGuardDriver implements ProtocolDriver
{
    public function __construct(
        private readonly WireGuardKeyGenerator $keyGenerator,
        private readonly WireGuardConfigGenerator $configGenerator,
        private readonly WireGuardConfigRepositoryInterface $configRepository,
        private readonly WireGuardReloader $reloader,
        private readonly WireGuardAddressAllocator $addressAllocator,
        private readonly WireGuardNodeConfig $nodeConfig,
    ) {}

    /**
     * Creates a WireGuard peer for the given config request.
     *
     * Workflow:
     *   1. Generate a fresh keypair + preshared key.
     *   2. Allocate the next available VPN IP from the subnet.
     *   3. Build the server [Peer] block and the client .conf file.
     *   4. Append the [Peer] block to wg0.conf.
     *   5. Reload the interface via wg syncconf.
     *      On reload failure the appended peer is removed before rethrowing.
     *   6. Return a ConfigResult carrying the client configuration.
     *
     * @throws WireGuardException
     */
    public function createConfig(CreateConfigData $data): ConfigResult
    {
        $keys      = $this->keyGenerator->generate();
        $clientIp  = $this->addressAllocator->allocate();

        $subnetPrefix        = explode('/', $this->nodeConfig->subnet, 2)[1];
        $clientAddress       = $clientIp . '/' . $subnetPrefix;
        $serverPeerAllowedIps = $clientIp . '/32';

        $peerInput = new WireGuardPeerInput(
            clientPrivateKey:    $keys->privateKey,
            clientPublicKey:     $keys->publicKey,
            presharedKey:        $keys->presharedKey,
            clientAddress:       $clientAddress,
            serverPeerAllowedIps: $serverPeerAllowedIps,
            serverPublicKey:     $this->nodeConfig->serverPublicKey,
            serverEndpoint:      $this->nodeConfig->serverEndpoint,
            clientAllowedIps:    $this->nodeConfig->clientAllowedIps,
            dns:                 $this->nodeConfig->dns,
            persistentKeepalive: $this->nodeConfig->persistentKeepalive,
        );

        $serverConfig = $this->configGenerator->generateServerPeerBlock($peerInput);
        $clientConfig = $this->configGenerator->generateClientConfig($peerInput);

        $this->configRepository->appendPeer($data->configId, $serverConfig->peerBlock);

        try {
            $this->reloader->reload();
        } catch (\Throwable $e) {
            // The peer block was written but the interface was not updated.
            // Roll back so the config file and the live interface stay in sync.
            try {
                $this->configRepository->removePeer($data->configId);
            } catch (\Throwable) {
                // Swallow — original exception carries the actionable information.
            }

            throw new WireGuardException(
                "WireGuard reload failed after writing peer for '{$data->configId}': {$e->getMessage()}",
                previous: $e,
            );
        }

        return new ConfigResult(
            configId:   $data->configId,
            protocol:   Protocol::WireGuard,
            identifier: $keys->publicKey,
            driverData: [
                'client_private_key' => $keys->privateKey,
                'client_public_key'  => $keys->publicKey,
                'preshared_key'      => $keys->presharedKey,
                'client_address'     => $clientAddress,
                'client_config'      => $clientConfig->configText,
            ],
            createdAt: new \DateTimeImmutable(),
        );
    }

    public function deleteConfig(string $configId): void
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getConfig(string $configId): ?ConfigResult
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function getConnections(): array
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function reload(): void
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function health(): array
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
