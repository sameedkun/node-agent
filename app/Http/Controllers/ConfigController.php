<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\CreateConfigData;
use App\Enums\Protocol;
use App\Http\Requests\CreateConfigRequest;
use App\Http\Resources\ConfigResultResource;
use App\Services\ConfigService;

final class ConfigController extends Controller
{
    public function __construct(
        private readonly ConfigService $configService,
    ) {}

    public function store(CreateConfigRequest $request): ConfigResultResource
    {
        $validated = $request->validated();

        $data = new CreateConfigData(
            configId:   $validated['config_id'],
            protocol:   Protocol::from($validated['protocol']),
            userId:     $validated['user_id'],
            deviceId:   $validated['device_id'],
            driverData: $validated['driver_data'],
        );

        return new ConfigResultResource($this->configService->create($data));
    }
}
