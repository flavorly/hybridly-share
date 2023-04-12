<?php

namespace Flavorly\HybridlyShare\Exceptions;

class HybridlyShareException extends \Exception
{
    public function __construct($driver)
    {
        parent::__construct("Driver '{$driver}' does not support Sharing to user");
    }
}
