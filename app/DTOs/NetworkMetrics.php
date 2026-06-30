<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class NetworkMetrics
{
    /**
     * @param NetworkInterfaceMetrics[] $interfaces  All non-loopback interfaces.
     * @param string|null               $defaultInterface  Name of the interface
     *                                                     carrying the default route,
     *                                                     or null if undetermined.
     */
    public function __construct(
        public array $interfaces,
        public ?string $defaultInterface,
    ) {}
}
