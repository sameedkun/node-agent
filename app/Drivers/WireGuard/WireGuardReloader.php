<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard;

use App\Drivers\WireGuard\Exceptions\WireGuardException;
use Illuminate\Support\Facades\Process;

/**
 * Reloads a running WireGuard interface without dropping existing tunnels.
 *
 * Uses wg syncconf rather than wg-quick down/up, so established sessions
 * are preserved across configuration changes. The reload sequence:
 *
 *   1. wg-quick strip <interface>  — strips wg-quick-only directives
 *                                    (Address, DNS, PostUp, etc.) that wg
 *                                    syncconf does not understand
 *   2. Write stripped output to a 0600 temp file
 *   3. wg syncconf <interface> <tempfile>  — atomically applies the diff
 *   4. Unlink temp file (always, even on failure)
 *
 * This is the portable equivalent of the bash one-liner:
 *   wg syncconf <interface> <(wg-quick strip <interface>)
 */
final class WireGuardReloader
{
    private readonly string $interface;

    public function __construct(?string $interface = null)
    {
        $this->interface = $interface ?? (string) config('wireguard.interface', 'wg0');
    }

    public function reload(): void
    {
        $stripped = $this->stripConfig();
        $tmp      = $this->writeTempConfig($stripped);

        try {
            $this->syncConf($tmp);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Runs wg-quick strip to obtain the configuration with only directives
     * that wg syncconf understands ([Interface] keys, [Peer] blocks).
     *
     * @throws WireGuardException
     */
    private function stripConfig(): string
    {
        $result = Process::run(['wg-quick', 'strip', $this->interface]);

        if (! $result->successful()) {
            throw new WireGuardException(
                "wg-quick strip failed for '{$this->interface}': {$result->errorOutput()}"
            );
        }

        $output = trim($result->output());

        if ($output === '') {
            throw new WireGuardException(
                "wg-quick strip produced empty output for '{$this->interface}' — "
                . "is the interface configured?"
            );
        }

        return $output . "\n";
    }

    /**
     * Writes the stripped configuration to a temporary file.
     *
     * Permissions are set to 0600 before writing because the output may
     * include the server private key.
     *
     * @throws WireGuardException
     */
    private function writeTempConfig(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'wg_sync_');

        if ($tmp === false) {
            throw new WireGuardException(
                'Cannot create temporary file for wg syncconf'
            );
        }

        chmod($tmp, 0600);

        if (file_put_contents($tmp, $contents) === false) {
            @unlink($tmp);
            throw new WireGuardException("Failed to write temporary config file: {$tmp}");
        }

        return $tmp;
    }

    /**
     * Applies the configuration atomically via wg syncconf.
     *
     * Only peers and keys that changed are updated; existing tunnels are
     * not interrupted.
     *
     * @throws WireGuardException
     */
    private function syncConf(string $configPath): void
    {
        $result = Process::run(['wg', 'syncconf', $this->interface, $configPath]);

        if (! $result->successful()) {
            throw new WireGuardException(
                "wg syncconf failed for '{$this->interface}': {$result->errorOutput()}"
            );
        }
    }
}
