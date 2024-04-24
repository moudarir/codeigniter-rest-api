<?php

use Moudarir\CodeigniterApi\Http\Server;

class PageNotFound extends Server
{

    public function index(): void
    {
        self::getResponse()->notFound();
    }
}
