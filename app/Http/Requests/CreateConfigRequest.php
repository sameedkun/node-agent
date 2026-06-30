<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Protocol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'config_id'   => ['required', 'string'],
            'protocol'    => ['required', 'string', new Enum(Protocol::class)],
            'user_id'     => ['required', 'string'],
            'device_id'   => ['required', 'string'],
            'driver_data' => ['required', 'array'],
        ];
    }
}
