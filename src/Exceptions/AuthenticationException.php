<?php

namespace Moudarir\CodeigniterApi\Exceptions;

use Moudarir\CodeigniterApi\Http\Config;

class AuthenticationException extends \Exception
{

    public function __construct(string $message, ?int $code = null)
    {
        parent::__construct($message, ($code ?: Config::HTTP_UNAUTHORIZED));
    }
}
