<?php

namespace Flavorly\HybridlyShare\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Flavorly\HybridlyShare\HybridlyShare
 */
class HybridlyShare extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Flavorly\HybridlyShare\HybridlyShare::class;
    }
}
