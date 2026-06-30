<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class MemoryMetrics
{
    public function __construct(
        public int $totalBytes,
        public int $usedBytes,
        public int $freeBytes,
        public float $usagePercent,
    ) {}
}
