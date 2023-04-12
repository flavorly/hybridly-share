<?php

use Flavorly\HybridlyShare\HybridlyShare;

if (! function_exists('hybridly_share')) {

    function hybridly_share(): HybridlyShare
    {
        return app(HybridlyShare::class);
    }
}
