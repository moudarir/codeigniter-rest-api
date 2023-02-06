<?php

namespace Moudarir\CodeigniterApi\Routes;

trait RouteTrait
{

    /**
     * @param array $route
     * @return void
     */
    public static function api(array &$route): void
    {
        foreach (self::API_ROUTES as $routes) {
            foreach ($routes['https'] as $method => $values) {
                if (is_array($values) && !empty($values)) {
                    foreach ($values as $key => $value) {
                        $route[$routes['uri'] . $key][$method] = $routes['controller'] . $value;
                    }
                } else {
                    $route[$routes['uri']][$method] = $routes['controller'];
                }
            }
        }
    }

    /**
     * @param array $route
     * @return void
     */
    public static function web(array &$route): void
    {
        foreach (self::ROUTES as $key => $value) {
            $route[$key] = $value;
        }
    }
}
