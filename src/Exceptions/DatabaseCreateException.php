<?php

namespace Moudarir\CodeigniterApi\Exceptions;

class DatabaseCreateException extends \Exception
{

    public function __construct()
    {
        parent::__construct("Error occurred when creating record.");
    }
}
