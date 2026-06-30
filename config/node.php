<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Node Identity
    |--------------------------------------------------------------------------
    |
    | These values uniquely identify this node to the Control Plane and are
    | used to authenticate inbound management requests. They are generated
    | once by `php artisan node:install` and must never be shared or logged.
    |
    | NODE_UUID   — RFC 4122 v4 UUID identifying this node.
    | NODE_SECRET — 64-character hex string used as the shared secret.
    |
    */

    'uuid'   => env('NODE_UUID', ''),
    'secret' => env('NODE_SECRET', ''),

];
