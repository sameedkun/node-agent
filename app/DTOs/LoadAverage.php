<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class LoadAverage
{
    public function __construct(
        public float $oneMinute,
        public float $fiveMinutes,
        public float $fifteenMinutes,
    ) {}
}
