<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\ControlPlaneAuthentication;
use App\Auth\SharedSecretAuthentication;
use App\Drivers\WireGuard\Contracts\WireGuardConfigRepositoryInterface;
use App\Drivers\WireGuard\DTOs\WireGuardNodeConfig;
use App\Drivers\WireGuard\WireGuardConfigRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WireGuardConfigRepositoryInterface::class, WireGuardConfigRepository::class);

        $this->app->singleton(ControlPlaneAuthentication::class, static function (): SharedSecretAuthentication {
            return new SharedSecretAuthentication(
                nodeUuid:   (string) config('node.uuid'),
                nodeSecret: (string) config('node.secret'),
            );
        });

        $this->app->singleton(WireGuardNodeConfig::class, static function (): WireGuardNodeConfig {
            $dns = config('wireguard.dns');
            $keepalive = config('wireguard.persistent_keepalive');

            return new WireGuardNodeConfig(
                serverPublicKey:    (string) config('wireguard.server_public_key'),
                serverEndpoint:     (string) config('wireguard.server_endpoint'),
                subnet:             (string) config('wireguard.subnet', '10.8.0.0/24'),
                clientAllowedIps:   (string) config('wireguard.client_allowed_ips', '0.0.0.0/0, ::/0'),
                dns:                ($dns !== null && $dns !== '') ? (string) $dns : null,
                persistentKeepalive: ($keepalive !== null && $keepalive !== '') ? (int) $keepalive : null,
            );
        });
    }

    public function boot(): void {}
}
