<?php
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * @property ErrorsHandler
 */
class ErrorsHandler
{

    public function index()
    {
        if (ENVIRONMENT === 'development') {
            if (class_exists(Run::class) && class_exists(PrettyPageHandler::class)) {
                $whoops = new Run;
                $whoops->prependHandler(new PrettyPageHandler);
                $whoops->register();
            }
        }
    }
}
