<?php

namespace Moudarir\CodeigniterApi\Routes;

class UserRoute
{

    use RouteTrait;

    const ANY = '(.*)';
    const EXT = '(\.)';
    const NUM = '([0-9]+)';
    const REGX = '([a-zA-Z0-9_-]+)';
    const USERS = 'users';
    const API_ROUTES = [
        [
            'uri' => self::USERS,
            'controller' => 'users/apiUsers',
            'https' => [
                'get' => [
                    "" => "",
                    self::EXT.self::REGX.self::ANY => "/format/$2$3",
                    "/".self::NUM => "/index/id/$1",
                    "/".self::NUM.self::EXT.self::REGX.self::ANY => "/index/id/$1/format/$3$4",
                ],
                'post' => [
                    "" => "",
                    self::EXT.self::REGX.self::ANY => "/format/$2$3",
                    "/login" => "/login",
                    "/login".self::EXT.self::REGX.self::ANY => "/login/format/$2$3",
                ],
                'put' => [
                    "/".self::NUM => "/index/id/$1",
                    "/".self::NUM.self::EXT.self::REGX.self::ANY => "/index/id/$1/format/$3$4",
                ],
                'head' => [],
                'options' => [],
            ],
        ]
    ];
}
