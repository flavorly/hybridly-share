<?php

namespace Flavorly\HybridlyShare\Exceptions;

class PrimaryKeyNotFoundException extends HybridlyShareException
{
    public function __construct()
    {
        parent::__construct('A primary key is required to share with flash. Please set the primary key on your model.');
    }
}
