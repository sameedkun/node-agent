<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard;

use App\Drivers\WireGuard\Contracts\WireGuardConfigRepositoryInterface;
use App\Drivers\WireGuard\DTOs\WireGuardNodeConfig;
use App\Drivers\WireGuard\Exceptions\WireGuardException;

/**
 * Sequential VPN address allocator.
 *
 * Scans the live wg0.conf for AllowedIPs entries to determine which host
 * addresses within the configured subnet are already in use, then returns
 * the lowest available address starting from host offset 2 (offset 1 is
 * reserved for the server interface).
 *
 * This is a temporary implementation — a proper allocator backed by the
 * database will replace this once persistence is in place.
 */
final class WireGuardAddressAllocator
{
    private const SERVER_HOST_OFFSET = 1;
    private const START_HOST_OFFSET  = 2;

    public function __construct(
        private readonly WireGuardConfigRepositoryInterface $repository,
        private readonly WireGuardNodeConfig $nodeConfig,
    ) {}

    /**
     * Returns the next available IP address in the VPN subnet.
     *
     * The returned string is a bare IP with no mask (e.g. "10.8.0.2").
     * The caller is responsible for appending the appropriate prefix:
     *   - /32 for the server-side peer AllowedIPs entry
     *   - /<subnet-prefix> for the client Interface Address line
     *
     * @throws WireGuardException  If the subnet is exhausted.
     */
    public function allocate(): string
    {
        [$baseAddress, $prefixLength] = explode('/', $this->nodeConfig->subnet, 2);

        $prefixLength = (int) $prefixLength;
        $baseIp       = ip2long($baseAddress);

        if ($baseIp === false) {
            throw new WireGuardException(
                "Invalid subnet base address in WireGuard config: '{$baseAddress}'"
            );
        }

        // Compute masks. PHP integers are 64-bit on modern systems; mask to 32 bits.
        $networkMask = $prefixLength === 0 ? 0 : ((~0 << (32 - $prefixLength)) & 0xFFFFFFFF);
        $hostMask    = ~$networkMask & 0xFFFFFFFF;
        $maxHost     = $hostMask - 1; // exclude broadcast address

        $usedHosts = $this->collectUsedHosts($baseIp, $networkMask, $hostMask);

        for ($host = self::START_HOST_OFFSET; $host <= $maxHost; $host++) {
            if (! in_array($host, $usedHosts, strict: true)) {
                return long2ip($baseIp | $host);
            }
        }

        throw new WireGuardException(
            "No available addresses in subnet {$this->nodeConfig->subnet} — all host offsets "
            . self::START_HOST_OFFSET . '–' . $maxHost . ' are in use'
        );
    }

    /**
     * Parses AllowedIPs lines from the server config and collects the host
     * offsets of addresses that belong to our subnet.
     *
     * @return list<int>
     */
    private function collectUsedHosts(int $baseIp, int $networkMask, int $hostMask): array
    {
        $contents = $this->repository->read();

        if ($contents === '') {
            return [];
        }

        $used = [];

        foreach (explode("\n", $contents) as $line) {
            $trimmed = trim($line);

            if (! str_starts_with($trimmed, 'AllowedIPs') || ! str_contains($trimmed, '=')) {
                continue;
            }

            // "AllowedIPs = 10.8.0.2/32"  or  "AllowedIPs = 10.8.0.2/32, fd00::2/128"
            $value = trim(explode('=', $trimmed, 2)[1]);

            foreach (explode(',', $value) as $cidr) {
                $ip   = trim(explode('/', trim($cidr), 2)[0]);
                $long = ip2long($ip);

                if ($long === false) {
                    continue;
                }

                // Only count addresses that fall within our subnet.
                if (($long & $networkMask) !== ($baseIp & $networkMask)) {
                    continue;
                }

                $used[] = (int) ($long & $hostMask);
            }
        }

        return $used;
    }
}
