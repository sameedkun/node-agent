<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class TelemetryData
{
    public function __construct(
        public \DateTimeImmutable $collectedAt,
        public array $metrics,
    ) {}
}
