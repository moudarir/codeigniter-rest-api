<?php

use Moudarir\CodeigniterApi\Http\Server;

class Welcome extends Server
{

    public function index(): void
    {
        self::getResponse()->badRequest();
    }
}
