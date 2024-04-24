<?php

namespace Moudarir\CodeigniterApi\Routes;

use Moudarir\CodeigniterApi\Exceptions\RouteNotFoundException;
use Moudarir\CodeigniterApi\Exceptions\UnknownReservedRouteException;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;

class Router
{

    /**
     * @var Route[]
     */
    private static array $routes = [];

    private static array $context = [
        'namespace' => [],
        'prefix'    => [],
        'params'    => [],
    ];

    public static array $compiled = [
        'routes'   => [],
        'paths'    => [],
        'names'    => [],
        'reserved' => [],
    ];

    /**
     * @var Route
     */
    private static Route $current;

    /**
     * @var string|callable
     */
    private static $error_404;

    public static function get(...$args): Route
    {
        $route = new Route('GET', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function post(...$args): Route
    {
        $route = new Route('POST', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function put(...$args): Route
    {
        $route = new Route('PUT', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function patch(...$args): Route
    {
        $route = new Route('PATCH', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function delete(...$args): Route
    {
        $route = new Route('DELETE', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function head(...$args): Route
    {
        $route = new Route('HEAD', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function options(...$args): Route
    {
        $route = new Route('OPTIONS', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function any(...$args): Route
    {
        $route = new Route('ANY', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function cli(...$args): Route
    {
        $route = new Route('CLI', $args);
        self::$routes[] = $route;

        return $route;
    }

    public static function match(array $methods, ...$args): Route
    {
        $route = new Route($methods, $args);
        self::$routes[] = $route;

        return $route;
    }

    /**
     * @param string $prefix
     * @param array|callable $attributes
     * @param callable|null $routes
     * @return void
     */
    public static function group(string $prefix, $attributes, ?callable $routes = null): void
    {
        if ($routes === null && \is_callable($attributes)) {
            $routes = $attributes;
            $attributes = [];
        }

        self::$context['prefix'][] = $prefix;

        if (\array_key_exists('namespace', $attributes)) {
            self::$context['namespace'][] = $attributes['namespace'];
        }

        \call_user_func($routes);

        \array_pop(self::$context['prefix']);

        if (\array_key_exists('namespace', $attributes)) {
            \array_pop(self::$context['namespace']);
        }
    }

    /**
     * Compiles all routes
     */
    public static function compileAll(): void
    {
        $routes = [];

        foreach (self::$routes as $route) {
            if ($route->getName() !== null) {
                if (\array_key_exists($route->getName(), self::$compiled['names'])) {
                    show_error('Duplicated "<strong>'. $route->getName() .'</strong>" named route');
                }

                self::$compiled['names'][$route->getName()] = clone $route;
            }

            foreach ($route->compile() as $compiled) {
                foreach ($compiled as $path => $action) {
                    foreach ($action as $method => $target) {
                        $routes[$path][$method] = $target;

                        $routePlaceholders = RouteParam::getPlaceholderReplacements();
                        $regexPath = \implode('\\/', \explode('/', $path));
                        $regexPath = \preg_replace(\array_keys($routePlaceholders), \array_values($routePlaceholders), $regexPath);
                        self::$compiled['paths']['#^' . $regexPath . '$#'][] = clone $route;
                    }
                }
            }
        }

        $config =& CommonHelper::apiConfig();
        $routes['default_controller'] = self::$compiled['reserved']['default_controller'] ?? $config['route_default_controller'];
        $routes['translate_uri_dashes'] = self::$compiled['reserved']['translate_uri_dashes'] ?? $config['route_translate_uri_dashes'];
        $routes['404_override'] = self::$compiled['reserved']['404_override'] ?? str_replace('@', '/', $config['route_404_override']);

        self::$compiled['routes'] = $routes;
    }

    /**
     * Creates a new resource route
     */
    public static function resource(string $name, string $controller, array $only = []): void
    {
        $routes = [
            'index'   => ['/',      ['GET']],
            'create'  => ['/',      ['POST']],
            'show'    => ['/{id}',  ['GET']],
            'update'  => ['/{id}',  ['PUT', 'PATCH']],
            'destroy' => ['/{id}',  ['DELETE']]
        ];

        if (!\is_array($only)) {
            $only = [];
        }

        foreach ($routes as $action => $props) {
            if (!empty($only) && !\in_array($action, $only)) {
                continue;
            }

            [$path, $methods] = $props;
            self::match($methods, $name . $path, $controller . '@' . $action)->name($name . '.' . $action);
        }
    }

    /**
     * Sets a CodeIgniter special route
     *
     * @param string $name
     * @param string|callable|bool $value
     * @throws UnknownReservedRouteException
     */
    public static function set(string $name, $value): void
    {
        if (!\in_array($name, ['404_override', 'default_controller', 'translate_uri_dashes'])) {
            throw new UnknownReservedRouteException($name);
        }

        if ($name === '404_override' && \is_callable($value)) {
            self::$error_404 = $value;
            $value = '';
        }

        self::$compiled['reserved'][$name] = $value;
    }

    /**
     * Gets the matching route of the provided URL
     *
     * @throws RouteNotFoundException
     */
    public static function getByUrl(string $url, ?string $requestMethod = null): Route
    {
        if (empty($requestMethod)) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? \strtoupper($_SERVER['REQUEST_METHOD']) : (!is_cli() ? 'GET' : 'CLI');
        } else {
            $requestMethod = \strtoupper($requestMethod);
        }

        // First, look for a direct match:
        $urlRegex = '#^' . \str_replace('/', '\\/', $url) . '$#';

        if (isset(self::$compiled['paths'][$urlRegex])) {
            foreach (self::$compiled['paths'][$urlRegex] as $route) {
                if (\in_array($requestMethod, $route->getMethods())) {
                    return $route;
                }
            }
        }

        // Then, loop into the array of compiled path
        foreach (self::$compiled['paths'] as $path => $routes) {
            if (\preg_match($path, $url)) {
                foreach ($routes as $route) {
                    if (\in_array($requestMethod, $route->getMethods())) {
                        return $route;
                    }
                }
            }
        }

        throw new RouteNotFoundException();
    }

    /**
     * Gets a route by its name
     *
     * @throws RouteNotFoundException
     */
    public static function getByName(string $name): Route
    {
        if (isset(self::$compiled['names'][$name])) {
            return self::$compiled['names'][$name];
        }

        throw new RouteNotFoundException();
    }

    /**
     * Gets all compiled routes
     */
    public static function getRoutes(): array
    {
        return self::$compiled['routes'];
    }

    /**
     * Gets the current route
     */
    public static function getCurrentRoute(): Route
    {
        return self::$current;
    }

    /**
     * Gets the custom 404 controller/callback
     *
     * @return callable|string|null
     */
    public static function getError404()
    {
        if (isset(self::$error_404) && !empty(self::$error_404)) {
            return self:: $error_404;
        }

        return self::$compiled['reserved']['404_override'] ?? null;
    }

    /**
     * Gets the static context of the route builder
     *
     * (This is used internally)
     * @return mixed
     */
    public static function getContext(?string $context = null)
    {
        return $context !== null ? self::$context[$context] : self::$context;
    }

    /**
     * Sets the current route
     */
    public static function setCurrentRoute(Route $route): void
    {
        self::$current = $route;
    }

    /**
     * Sets a (global) default value for a sticky parameter
     */
    public static function setDefaultParam(string $name, string $value): void
    {
        self::$context['params'][$name] = $value;
    }

    /**
     * Gets all (global) default sticky parameters values
     */
    public static function getDefaultParams(): array
    {
        return self::$context['params'];
    }
}
