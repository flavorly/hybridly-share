<?php

namespace Flavorly\HybridlyShare\Exceptions;

class DriverNotSupportedException extends HybridlyShareException
{
    public function __construct($driver)
    {
        parent::__construct("Driver '{$driver}' is not supported on Hybridly Share.");
    }
}
