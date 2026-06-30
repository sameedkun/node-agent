<?php

declare(strict_types=1);

namespace App\Collectors;

use App\Contracts\MetricsCollector;
use App\DTOs\NetworkInterfaceMetrics;
use App\DTOs\NetworkMetrics;
use App\Exceptions\MetricsCollectionException;

final class NetworkMetricsCollector implements MetricsCollector
{
    private const LOOPBACK = 'lo';

    /**
     * /proc/net/dev field indices (0-based, after splitting on the colon).
     *
     * Receive:   0=bytes  1=packets  2=errs  3=drop  4=fifo  5=frame  6=compressed  7=multicast
     * Transmit:  8=bytes  9=packets 10=errs 11=drop 12=fifo 13=colls 14=carrier    15=compressed
     */
    private const RX_BYTES   = 0;
    private const RX_PACKETS = 1;
    private const RX_ERRORS  = 2;
    private const TX_BYTES   = 8;
    private const TX_PACKETS = 9;
    private const TX_ERRORS  = 10;

    /** RTF_GATEWAY flag in /proc/net/route Flags column. */
    private const RTF_GATEWAY = 0x0002;

    public function collect(): array
    {
        return ['network' => $this->collectNetworkMetrics()];
    }

    public function collectNetworkMetrics(): NetworkMetrics
    {
        return new NetworkMetrics(
            interfaces:       $this->parseInterfaces(),
            defaultInterface: $this->resolveDefaultInterface(),
        );
    }

    // -------------------------------------------------------------------------
    // /proc/net/dev
    // -------------------------------------------------------------------------

    /**
     * Parses /proc/net/dev and returns one DTO per non-loopback interface.
     *
     * File layout:
     *   Line 0: column group header  ("Inter-|Receive|Transmit")
     *   Line 1: field name header    ("face|bytes packets errs ...")
     *   Line 2+: one interface per line, format:
     *     <iface>: <rx_bytes> <rx_packets> <rx_errs> ... <tx_bytes> <tx_packets> <tx_errs> ...
     *
     * @return NetworkInterfaceMetrics[]
     */
    private function parseInterfaces(): array
    {
        $lines = explode("\n", trim($this->readFile('/proc/net/dev')));

        if (count($lines) < 3) {
            throw new MetricsCollectionException(
                '/proc/net/dev has fewer lines than expected — cannot parse network interfaces.'
            );
        }

        $interfaces = [];

        foreach (array_slice($lines, 2) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $colonPos = strpos($line, ':');

            if ($colonPos === false) {
                throw new MetricsCollectionException(
                    "Malformed line in /proc/net/dev — missing colon separator: '{$line}'"
                );
            }

            $name   = trim(substr($line, 0, $colonPos));
            $fields = preg_split('/\s+/', trim(substr($line, $colonPos + 1)));

            if ($fields === false || count($fields) < 16) {
                throw new MetricsCollectionException(
                    "Interface '{$name}' in /proc/net/dev has fewer than 16 fields."
                );
            }

            if ($name === self::LOOPBACK) {
                continue;
            }

            $interfaces[] = new NetworkInterfaceMetrics(
                name:      $name,
                rxBytes:   (int) $fields[self::RX_BYTES],
                txBytes:   (int) $fields[self::TX_BYTES],
                rxPackets: (int) $fields[self::RX_PACKETS],
                txPackets: (int) $fields[self::TX_PACKETS],
                rxErrors:  (int) $fields[self::RX_ERRORS],
                txErrors:  (int) $fields[self::TX_ERRORS],
            );
        }

        return $interfaces;
    }

    // -------------------------------------------------------------------------
    // /proc/net/route — default interface
    // -------------------------------------------------------------------------

    /**
     * Scans /proc/net/route for the default gateway entry and returns the
     * interface name carrying that route.
     *
     * File layout (tab-separated):
     *   Iface  Destination  Gateway  Flags  RefCnt  Use  Metric  Mask  MTU  Window  IRTT
     *
     * The default route has Destination == "00000000" and Flags & RTF_GATEWAY != 0.
     * All numeric columns are little-endian hex.
     *
     * Returns null if the file is unavailable or no default route is found —
     * both are valid states (e.g. a node behind a bridge or in a container).
     */
    private function resolveDefaultInterface(): ?string
    {
        if (! is_file('/proc/net/route') || ! is_readable('/proc/net/route')) {
            return null;
        }

        $lines = explode("\n", trim($this->readFile('/proc/net/route')));

        // Skip the header line.
        foreach (array_slice($lines, 1) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line);

            if ($parts === false || count($parts) < 8) {
                continue;
            }

            $iface       = $parts[0];
            $destination = $parts[1];
            $flags       = hexdec($parts[3]);

            if ($destination === '00000000' && ($flags & self::RTF_GATEWAY) !== 0) {
                return $iface;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // File helpers
    // -------------------------------------------------------------------------

    private function readFile(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new MetricsCollectionException(
                "Cannot read '{$path}': file not found or not readable. "
                . 'This collector requires a Linux environment with /proc mounted.'
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new MetricsCollectionException("Failed to read contents of '{$path}'.");
        }

        return $contents;
    }
}
