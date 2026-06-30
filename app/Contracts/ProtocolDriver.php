<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ConfigResult;
use App\DTOs\CreateConfigData;

interface ProtocolDriver
{
    /**
     * Creates a new VPN configuration and returns its result.
     */
    public function createConfig(CreateConfigData $data): ConfigResult;

    /**
     * Permanently removes a configuration by its Control Plane UUID.
     */
    public function deleteConfig(string $configId): void;

    /**
     * Retrieves an existing configuration, or null if it does not exist.
     */
    public function getConfig(string $configId): ?ConfigResult;

    /**
     * Returns raw active-connection facts for this protocol.
     * Shape is driver-defined; no business decisions are made here.
     *
     * @return array<mixed>
     */
    public function getConnections(): array;

    /**
     * Signals the VPN service to reload its configuration without full restart.
     */
    public function reload(): void;

    /**
     * Returns raw health facts for this protocol service.
     * No scoring, no thresholds — facts only.
     *
     * @return array<mixed>
     */
    public function health(): array;
}
