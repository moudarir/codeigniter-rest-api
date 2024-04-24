<?php

namespace Moudarir\CodeigniterApi\Exceptions;

class RouteNotFoundException extends \Exception
{

    public function __construct()
    {
        parent::__construct("Route not found");
    }
}
