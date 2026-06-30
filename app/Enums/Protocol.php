<?php

declare(strict_types=1);

namespace App\Enums;

enum Protocol: string
{
    case WireGuard = 'wireguard';
    case OpenVPN   = 'openvpn';
    case SingBox   = 'singbox';
    case Xray      = 'xray';
    case Hysteria  = 'hysteria';
}
