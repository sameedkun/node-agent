<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConfigResultResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'config_id'   => $this->configId,
            'protocol'    => $this->protocol->value,
            'identifier'  => $this->identifier,
            'driver_data' => $this->driverData,
            'created_at'  => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
