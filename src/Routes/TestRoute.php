<?php

namespace Moudarir\CodeigniterApi\Routes;

class TestRoute
{

    use RouteTrait;

    const ALPHA = '([a-zA-Z\-]+)';
    const ALNUM = '([a-zA-Z0-9\-]+)';
    const ENCRYPT = '([a-zA-Z0-9\-%:+,\._=~]+)';
    const TEST = 'test';
    const ROUTES = [
        self::TEST."/".self::ALPHA => 'tests/$1Test',
        self::TEST."/".self::ALPHA."/".self::ENCRYPT => 'tests/$1Test/$2',
        self::TEST."/".self::ALPHA."/".self::ENCRYPT."/".self::ENCRYPT => 'tests/$1Test/$2/$3',
        self::TEST."/".self::ALPHA."/".self::ENCRYPT."/".self::ENCRYPT."/".self::ALNUM => 'tests/$1Test/$2/$3/$4',
    ];
}
