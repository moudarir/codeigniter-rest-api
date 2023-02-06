<?php

class CoreTest extends CI_Controller
{

    /**
     * CoreTest constructor.
     */
    public function __construct()
    {
        parent::__construct();
        load_class('Model', 'core');
    }

    /**
     * CoreTest destructor.
     */
    public function __destruct()
    {
        dd((float)$this->benchmark->elapsed_time('total_execution_time_start'));
    }
}
