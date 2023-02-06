<?php

/**
 * @property PageNotFound
 */
class PageNotFound extends CoreServer
{

    /**
     * PageNotFound constructor.
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
        self::getResponse()->notFound("Chemin introuvable.");
    }
}
