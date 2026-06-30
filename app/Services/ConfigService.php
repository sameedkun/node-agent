<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ConfigResult;
use App\DTOs\CreateConfigData;
use App\Factories\DriverFactory;

/**
 * Application service for VPN configuration lifecycle operations.
 *
 * This service sits at the boundary between the HTTP/command/queue layer
 * and the protocol driver layer. Its sole responsibility is to route a
 * configuration request to the correct driver and return the result.
 *
 * It is intentionally protocol-agnostic: it never references WireGuard,
 * OpenVPN, Sing-box, Xray, Hysteria, or any other protocol by name.
 * All protocol-specific logic lives inside the driver resolved by
 * DriverFactory. Adding support for a new protocol requires only a new
 * driver class and a new case in DriverFactory — this service changes
 * never.
 *
 * What this service does NOT do:
 *   - HTTP validation or authentication (controller responsibility)
 *   - JSON encoding or response shaping (controller responsibility)
 *   - Database persistence (a future repository layer responsibility)
 *   - Business decisions (user limits, plan checks, health scoring)
 */
final class ConfigService
{
    public function __construct(
        private readonly DriverFactory $driverFactory,
    ) {}

    /**
     * Creates a VPN configuration for the given request.
     *
     * Resolves the driver for the protocol declared in $data, delegates
     * the creation entirely to that driver, and returns its ConfigResult.
     *
     * @throws \App\Exceptions\UnsupportedProtocolException  If the node has no
     *         driver registered for the requested protocol.
     * @throws \RuntimeException  On any driver-level failure (key generation,
     *         config file write, interface reload, etc.).
     */
    public function create(CreateConfigData $data): ConfigResult
    {
        $driver = $this->driverFactory->make($data->protocol);

        return $driver->createConfig($data);
    }
}
