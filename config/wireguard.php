<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | WireGuard Interface Name
    |--------------------------------------------------------------------------
    |
    | The name of the WireGuard network interface managed by this node.
    | Must match the base name of the configuration file under /etc/wireguard/
    | (e.g. "wg0" corresponds to /etc/wireguard/wg0.conf).
    |
    */

    'interface' => env('WG_INTERFACE', 'wg0'),

    /*
    |--------------------------------------------------------------------------
    | Server Identity
    |--------------------------------------------------------------------------
    |
    | The server's WireGuard public key and its publicly reachable endpoint.
    | These are written into every client configuration file so clients know
    | where to connect and can authenticate the server peer.
    |
    | WG_SERVER_PUBLIC_KEY — base64-encoded public key (44 characters).
    | WG_SERVER_ENDPOINT   — host:port, e.g. "203.0.113.1:51820".
    |
    */

    'server_public_key' => env('WG_SERVER_PUBLIC_KEY', ''),
    'server_endpoint'   => env('WG_SERVER_ENDPOINT', ''),

    /*
    |--------------------------------------------------------------------------
    | VPN Subnet
    |--------------------------------------------------------------------------
    |
    | CIDR block used for the WireGuard VPN network.
    | The server is assumed to hold .1; clients are allocated from .2 upward.
    |
    */

    'subnet' => env('WG_SUBNET', '10.8.0.0/24'),

    /*
    |--------------------------------------------------------------------------
    | Client Defaults
    |--------------------------------------------------------------------------
    |
    | Applied to every generated client configuration unless the Control Plane
    | overrides them via CreateConfigData::driverData.
    |
    | WG_CLIENT_ALLOWED_IPS    — routes tunnelled through the VPN.
    |                            "0.0.0.0/0, ::/0" = full tunnel.
    | WG_DNS                   — comma-separated DNS servers for the client
    |                            interface. Leave empty to omit the DNS line.
    | WG_PERSISTENT_KEEPALIVE  — keepalive in seconds. Leave empty to omit.
    |
    */

    'client_allowed_ips'   => env('WG_CLIENT_ALLOWED_IPS', '0.0.0.0/0, ::/0'),
    'dns'                  => env('WG_DNS', ''),
    'persistent_keepalive' => env('WG_PERSISTENT_KEEPALIVE', ''),

];
