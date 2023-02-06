<?php

/**
 * @property InitAppCore
 */
class InitAppCore
{

    /**
     * @return void
     */
    public function initialize(): void
    {
        spl_autoload_register([__CLASS__, 'customCores']);
    }

    /**
     * @param string $class_name
     */
    public function customCores(string $class_name)
    {
        if (strpos($class_name, 'CI_') !== 0) {
            $class_file = $class_name.'.php';
            if (is_readable(APPPATH.'core'.DIRECTORY_SEPARATOR.$class_file)) {
                require_once(APPPATH.'core'.DIRECTORY_SEPARATOR.$class_file);
            }
        }
    }
}
