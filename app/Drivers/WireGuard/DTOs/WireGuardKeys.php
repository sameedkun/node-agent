<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard\DTOs;

final readonly class WireGuardKeys
{
    public function __construct(
        public string $privateKey,
        public string $publicKey,
        public string $presharedKey,
    ) {}
}
