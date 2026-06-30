<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard\Contracts;

use App\Drivers\WireGuard\Exceptions\WireGuardException;

interface WireGuardConfigRepositoryInterface
{
    public function exists(): bool;

    /** @throws WireGuardException */
    public function read(): string;

    /** @throws WireGuardException */
    public function appendPeer(string $configId, string $peerBlock): void;

    /** @throws WireGuardException */
    public function removePeer(string $configId): bool;
}
