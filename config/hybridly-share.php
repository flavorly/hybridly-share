<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver Configuration
    |--------------------------------------------------------------------------
    |
    | You can configure hybridly share to use session or cache as the driver.
    | when using the cache driver hybridly flash will leverage your current
    | cache driver and attempt to save the temporary shared keys there.
    | A unique key is used to generate the unique key for each user
    |
    | Drivers: 'cache' or 'session' are supported.
    | Prefix Key : hybridly_container_
    | Cache TTL : Time in seconds to store the keys in cache.
    */

    'prefix_key' => 'hybridly_container_',
    'driver' => 'session',

    'session-driver' => \Flavorly\HybridlyShare\Drivers\SessionDriver::class,
    'cache-driver' => \Flavorly\HybridlyShare\Drivers\CacheDriver::class,

    'cache-ttl' => 60,

    /*
    |--------------------------------------------------------------------------
    | Persistent Keys
    |--------------------------------------------------------------------------
    |
    | Here you may configure the keys that should be persisted on the session,
    | even if they are empty they will be mapped to their primitives configured here.
    |
    */
    'persistent-keys' => [
        // foo, bar, baz
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore URLs & Params
    |--------------------------------------------------------------------------
    |
    | The URls to ignore by default, because hybridly runs on web middleware
    | Default For URLS: ['broadcasting/auth','nova-api*','filament-api*', etc...]
    |
    */
    'ignore_urls' => [
        'nova-api*',
        'filament-api*',
        'broadcasting/auth',
        'telescope*',
        'horizon*',
        '_debugbar*',
        '_ignition*',
    ],
];
