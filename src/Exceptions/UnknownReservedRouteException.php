<?php

namespace Moudarir\CodeigniterApi\Exceptions;

class UnknownReservedRouteException extends \Exception
{

    public function __construct(string $name)
    {
        parent::__construct('Unknown reserved route "' . $name . '"');
    }
}
