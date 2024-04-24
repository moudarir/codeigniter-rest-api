<?php

namespace Moudarir\CodeigniterApi\Exceptions;

use Moudarir\CodeigniterApi\Http\Config;

class FormatException extends \Exception
{

    public function __construct(string $message)
    {
        parent::__construct($message, Config::HTTP_BAD_REQUEST);
    }
}
