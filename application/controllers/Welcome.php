<?php

/**
 * @property Welcome
 */
class Welcome extends CoreServer
{

    /**
     * Welcome constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function index(): void
    {
        self::getResponse()->badRequest();
    }
}
