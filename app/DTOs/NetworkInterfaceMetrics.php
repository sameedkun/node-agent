<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class NetworkInterfaceMetrics
{
    public function __construct(
        public string $name,
        public int $rxBytes,
        public int $txBytes,
        public int $rxPackets,
        public int $txPackets,
        public int $rxErrors,
        public int $txErrors,
    ) {}
}
