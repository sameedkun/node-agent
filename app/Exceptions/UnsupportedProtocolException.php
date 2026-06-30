<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Protocol;

final class UnsupportedProtocolException extends \RuntimeException
{
    public function __construct(Protocol $protocol)
    {
        parent::__construct(
            "Protocol '{$protocol->value}' is not supported by this node."
        );
    }
}
