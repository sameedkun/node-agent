<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\Protocol;

final readonly class ConfigResult
{
    /**
     * @param string             $configId    The Control Plane's UUID.
     * @param Protocol           $protocol    Which protocol produced this result.
     * @param string             $identifier  Driver's stable reference for this
     *                                        config (e.g. WireGuard peer public key,
     *                                        OpenVPN client CN).
     * @param array              $driverData  Protocol-specific output. Opaque at
     *                                        this layer (peer block, .ovpn content,
     *                                        sing-box outbound object, etc.).
     * @param \DateTimeImmutable $createdAt   UTC timestamp of config creation.
     */
    public function __construct(
        public string $configId,
        public Protocol $protocol,
        public string $identifier,
        public array $driverData,
        public \DateTimeImmutable $createdAt,
    ) {}
}
