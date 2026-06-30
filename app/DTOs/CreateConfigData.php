<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CreateConfigData
{
    /**
     * @param string $configId    The Control Plane's UUID for this configuration.
     * @param string $userId      Reference identifier — not a local foreign key.
     * @param string $deviceId    Reference identifier — not a local foreign key.
     * @param array  $driverData  Protocol-specific parameters supplied by the
     *                            Control Plane. Opaque at this layer.
     */
    public function __construct(
        public string $configId,
        public string $userId,
        public string $deviceId,
        public array $driverData,
    ) {}
}
