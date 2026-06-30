<?php

declare(strict_types=1);

namespace App\Contracts;

interface MetricsCollector
{
    public function collect(): array;
}
