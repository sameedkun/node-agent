<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class SystemMetrics
{
    public function __construct(
        public float $cpuUsagePercent,
        public MemoryMetrics $memory,
        public DiskMetrics $disk,
        public int $uptimeSeconds,
        public LoadAverage $loadAverage,
        public string $hostname,
        public \DateTimeImmutable $recordedAt,
    ) {}
}
