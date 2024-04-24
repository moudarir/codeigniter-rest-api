<?php

namespace Moudarir\CodeigniterApi\Helpers;

use Moudarir\CodeigniterApi\Exceptions\RouteNotFoundException;
use Moudarir\CodeigniterApi\Routes\Router;
use function redirect;
use function site_url;

class RouteHelper
{

    public static function path(?string $name = null, array $params = [], array $query_params = []): string
    {
        try {
            $route = $name === null ? Router::getCurrentRoute() : Router::getByName($name);
            $path = '/'.$route->buildPath($params);

            if (!empty($query_params)) {
                $path .= '?'.\http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);
            }

            return $path;
        } catch (RouteNotFoundException|\Exception $exception) {
            CommonHelper::logException($exception);
        }

        return '';
    }

    public static function url(?string $name = null, array $params = [], array $query_params = []): string
    {
        return site_url(self::path($name, $params, $query_params));
    }

    public static function redirect(?string $name = null, array $params = [], array $query_params = [], string $method = 'refresh')
    {
        redirect(self::path($name, $params, $query_params), $method);
    }

    public static function exists(string $name): bool
    {
        return isset(Router::$compiled['names'][$name]);
    }
}
