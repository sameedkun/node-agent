<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MetricsCollector;
use App\DTOs\TelemetryData;

final class TelemetryService
{
    public function __construct(
        private readonly MetricsCollector $collector,
    ) {}

    public function collect(): TelemetryData
    {
        return new TelemetryData(
            collectedAt: new \DateTimeImmutable(),
            metrics: $this->collector->collect(),
        );
    }
}
