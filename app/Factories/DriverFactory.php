<?php

declare(strict_types=1);

namespace App\Factories;

use App\Contracts\ProtocolDriver;
use App\Drivers\WireGuard\WireGuardDriver;
use App\Enums\Protocol;
use App\Exceptions\UnsupportedProtocolException;
use Illuminate\Contracts\Container\Container;

final class DriverFactory
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Resolves the correct ProtocolDriver for the given protocol.
     * The driver is built through the service container so that any
     * dependencies it declares in its constructor are injected automatically.
     *
     * @throws UnsupportedProtocolException  When no driver exists for the protocol.
     */
    public function make(Protocol $protocol): ProtocolDriver
    {
        return match ($protocol) {
            Protocol::WireGuard => $this->container->make(WireGuardDriver::class),
            default             => throw new UnsupportedProtocolException($protocol),
        };
    }
}
