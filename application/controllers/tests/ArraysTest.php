<?php

use Moudarir\CodeigniterApi\Models\Api\ApiKey;

/**
 * @property ArraysTest
 */
class ArraysTest extends CoreTest
{

    /**
     * ArraysTest constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function key()
    {
        $id = (new ApiKey())
            ->setUserId(1)
            ->setKey()
            ->setUsername()
            ->setPassword()
            ->setIpAddresses()
            ->create();
        $item = (new ApiKey())($id);
        dump($item);
    }
}
