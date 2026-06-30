<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard;

use App\Drivers\WireGuard\DTOs\WireGuardKeys;
use App\Drivers\WireGuard\Exceptions\WireGuardException;
use Illuminate\Support\Facades\Process;

final class WireGuardKeyGenerator
{
    /**
     * Generates a complete WireGuard keypair and preshared key.
     *
     * Sequence:
     *   1. wg genkey              → private key
     *   2. wg pubkey  < privkey   → public key (derived from private)
     *   3. wg genpsk              → preshared key (independent symmetric key)
     *
     * @throws WireGuardException  If any wg command is unavailable or fails.
     */
    public function generate(): WireGuardKeys
    {
        $privateKey   = $this->run('wg genkey');
        $publicKey    = $this->runWithInput($privateKey, 'wg pubkey');
        $presharedKey = $this->run('wg genpsk');

        return new WireGuardKeys(
            privateKey:   $privateKey,
            publicKey:    $publicKey,
            presharedKey: $presharedKey,
        );
    }

    /**
     * Runs a command and returns trimmed stdout.
     *
     * @throws WireGuardException
     */
    private function run(string $command): string
    {
        try {
            $result = Process::run($command);
        } catch (\Throwable $e) {
            throw new WireGuardException(
                "Failed to start WireGuard command '{$command}': {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($result->failed()) {
            $stderr = trim($result->errorOutput());
            throw new WireGuardException(
                "WireGuard command '{$command}' exited with a non-zero status"
                . ($stderr !== '' ? ": {$stderr}" : '.'),
            );
        }

        return trim($result->output());
    }

    /**
     * Runs a command with the given string as stdin and returns trimmed stdout.
     *
     * Used to pipe the private key into `wg pubkey`.
     *
     * @throws WireGuardException
     */
    private function runWithInput(string $input, string $command): string
    {
        try {
            $result = Process::input($input)->run($command);
        } catch (\Throwable $e) {
            throw new WireGuardException(
                "Failed to start WireGuard command '{$command}': {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($result->failed()) {
            $stderr = trim($result->errorOutput());
            throw new WireGuardException(
                "WireGuard command '{$command}' exited with a non-zero status"
                . ($stderr !== '' ? ": {$stderr}" : '.'),
            );
        }

        return trim($result->output());
    }
}
