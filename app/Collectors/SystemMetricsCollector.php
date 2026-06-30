<?php

declare(strict_types=1);

namespace App\Collectors;

use App\Contracts\MetricsCollector;
use App\DTOs\DiskMetrics;
use App\DTOs\LoadAverage;
use App\DTOs\MemoryMetrics;
use App\DTOs\SystemMetrics;
use App\Exceptions\MetricsCollectionException;

final class SystemMetricsCollector implements MetricsCollector
{
    /**
     * Returns ['system' => SystemMetrics] for consumption by TelemetryService.
     * Use collectSystemMetrics() directly for typed access.
     */
    public function collect(): array
    {
        return ['system' => $this->collectSystemMetrics()];
    }

    public function collectSystemMetrics(): SystemMetrics
    {
        [$sample1, $sample2] = $this->takeCpuSamples();

        return new SystemMetrics(
            cpuUsagePercent: $this->calculateCpuPercent($sample1, $sample2),
            memory: $this->readMemory(),
            disk: $this->readDisk(),
            uptimeSeconds: $this->readUptime(),
            loadAverage: $this->readLoadAverage(),
            hostname: $this->readHostname(),
            recordedAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    // -------------------------------------------------------------------------
    // CPU
    // -------------------------------------------------------------------------

    /**
     * Takes two /proc/stat samples 100 ms apart.
     * A single snapshot gives cumulative jiffies since boot — not a rate.
     *
     * @return array{array<int>, array<int>}
     */
    private function takeCpuSamples(): array
    {
        $sample1 = $this->parseProcStatCpu();
        usleep(100_000);
        $sample2 = $this->parseProcStatCpu();

        return [$sample1, $sample2];
    }

    /**
     * Parses the first "cpu" line of /proc/stat into an array of integer jiffies.
     *
     * Field order: user, nice, system, idle, iowait, irq, softirq, steal, guest, guest_nice
     *
     * @return array<int>
     */
    private function parseProcStatCpu(): array
    {
        $line = $this->readFirstLine('/proc/stat');

        if (! str_starts_with($line, 'cpu')) {
            throw new MetricsCollectionException(
                "Unexpected format in /proc/stat: first line does not start with 'cpu'."
            );
        }

        $parts = preg_split('/\s+/', trim($line));

        if ($parts === false || count($parts) < 6) {
            throw new MetricsCollectionException(
                'Could not parse CPU jiffies from /proc/stat: too few fields.'
            );
        }

        array_shift($parts); // drop the 'cpu' label

        return array_map('intval', $parts);
    }

    /**
     * Calculates CPU usage percentage from two jiffy snapshots.
     *
     * idle includes both idle (index 3) and iowait (index 4) — both represent
     * time the CPU spent not doing useful work.
     */
    private function calculateCpuPercent(array $sample1, array $sample2): float
    {
        $total1 = array_sum($sample1);
        $total2 = array_sum($sample2);

        $idle1 = ($sample1[3] ?? 0) + ($sample1[4] ?? 0);
        $idle2 = ($sample2[3] ?? 0) + ($sample2[4] ?? 0);

        $totalDelta = $total2 - $total1;
        $idleDelta  = $idle2 - $idle1;

        if ($totalDelta === 0) {
            return 0.0;
        }

        return round(($totalDelta - $idleDelta) / $totalDelta * 100, 2);
    }

    // -------------------------------------------------------------------------
    // Memory
    // -------------------------------------------------------------------------

    /**
     * Parses /proc/meminfo into a key => bytes map.
     *
     * Uses MemAvailable (not MemFree) for "used" calculation.
     * MemAvailable is the kernel's estimate of reclaimable memory — what tools
     * like `free -h` report as "available".
     */
    private function readMemory(): MemoryMetrics
    {
        $info = $this->parseMemInfo();

        $this->requireMemInfoKey($info, 'MemTotal');
        $this->requireMemInfoKey($info, 'MemAvailable');
        $this->requireMemInfoKey($info, 'MemFree');

        $total     = $info['MemTotal'];
        $available = $info['MemAvailable'];
        $free      = $info['MemFree'];
        $used      = $total - $available;

        return new MemoryMetrics(
            totalBytes:   $total,
            usedBytes:    $used,
            freeBytes:    $free,
            usagePercent: $total > 0 ? round($used / $total * 100, 2) : 0.0,
        );
    }

    /**
     * @return array<string, int>  key => bytes (kB values multiplied by 1024)
     */
    private function parseMemInfo(): array
    {
        $contents = $this->readFile('/proc/meminfo');
        $result   = [];

        foreach (explode("\n", $contents) as $line) {
            // Matches lines like: "MemTotal:       16384000 kB"
            if (preg_match('/^(\w+):\s+(\d+)\s+kB$/', trim($line), $matches)) {
                $result[$matches[1]] = (int) $matches[2] * 1024;
            }
        }

        return $result;
    }

    /**
     * @param array<string, int> $info
     */
    private function requireMemInfoKey(array $info, string $key): void
    {
        if (! array_key_exists($key, $info)) {
            throw new MetricsCollectionException(
                "Expected key '{$key}' not found in /proc/meminfo."
            );
        }
    }

    // -------------------------------------------------------------------------
    // Disk
    // -------------------------------------------------------------------------

    /**
     * Uses PHP's built-in disk_total_space() and disk_free_space() for the
     * root filesystem. These call statvfs(2) internally — no shell required.
     */
    private function readDisk(): DiskMetrics
    {
        $total = disk_total_space('/');
        $free  = disk_free_space('/');

        if ($total === false || $free === false) {
            throw new MetricsCollectionException(
                'Failed to read disk metrics for the root filesystem.'
            );
        }

        $total = (int) $total;
        $free  = (int) $free;
        $used  = $total - $free;

        return new DiskMetrics(
            totalBytes:   $total,
            usedBytes:    $used,
            freeBytes:    $free,
            usagePercent: $total > 0 ? round($used / $total * 100, 2) : 0.0,
        );
    }

    // -------------------------------------------------------------------------
    // Uptime
    // -------------------------------------------------------------------------

    /**
     * /proc/uptime contains two space-separated floats:
     *   <uptime_seconds> <idle_seconds>
     *
     * Only the first value is used.
     */
    private function readUptime(): int
    {
        $line = $this->readFirstLine('/proc/uptime');

        [$uptime] = explode(' ', $line);

        return (int) $uptime;
    }

    // -------------------------------------------------------------------------
    // Load average
    // -------------------------------------------------------------------------

    /**
     * sys_getloadavg() reads /proc/loadavg and returns [1min, 5min, 15min].
     * Returns false on failure (Windows, or a broken /proc).
     */
    private function readLoadAverage(): LoadAverage
    {
        $averages = sys_getloadavg();

        if ($averages === false) {
            throw new MetricsCollectionException(
                'sys_getloadavg() returned false — /proc/loadavg may be unavailable.'
            );
        }

        return new LoadAverage(
            oneMinute:     $averages[0],
            fiveMinutes:   $averages[1],
            fifteenMinutes: $averages[2],
        );
    }

    // -------------------------------------------------------------------------
    // Hostname
    // -------------------------------------------------------------------------

    private function readHostname(): string
    {
        $hostname = gethostname();

        if ($hostname === false) {
            throw new MetricsCollectionException('gethostname() failed.');
        }

        return $hostname;
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

    private function readFirstLine(string $path): string
    {
        return explode("\n", $this->readFile($path))[0];
    }
}
