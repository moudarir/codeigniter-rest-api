<?php

namespace Moudarir\CodeigniterApi\Routes;

use Moudarir\CodeigniterApi\Exceptions\RouteNotFoundException;
use Moudarir\CodeigniterApi\Exceptions\UnknownReservedRouteException;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use Moudarir\CodeigniterApi\Helpers\StringHelper;
use function config_item;
use function get_instance;
use function is_cli;
use function load_class;
use function show_404;
use function show_error;
use const APPPATH;

class Hook
{

    public static function initialize(): array
    {
        $hooks = [];
        $hooks['pre_system'][] = static fn () => self::preSystem();
        $hooks['pre_controller'][] = static function () {
            global $params, $URI, $class, $method;
            self::preController($params, $URI, $class, $method);
        };
        $hooks['post_controller_constructor'][] = static function () {
            global $params;
            self::postControllerConstructor($params);
        };

        return $hooks;
    }

    private static function preSystem()
    {
        $config =& CommonHelper::apiConfig();
        $isAjax = CommonHelper::isAjaxRequest();
        $isCli = is_cli();
        $isWeb = !$isCli && !$isAjax;

        if ($isWeb === true && \file_exists(APPPATH.'routes/web.php')) {
            require_once(APPPATH . 'routes/web.php');
        }

        if ($isAjax === true && \file_exists(APPPATH.'routes/api.php')) {
            require_once(APPPATH . 'routes/api.php');
        }

        if ($isCli === true && \file_exists(APPPATH.'routes/cli.php')) {
            require_once(APPPATH . 'routes/cli.php');

            try {
                Router::set('default_controller', $config['route_fake_default_controller']);
            } catch (UnknownReservedRouteException $exception) {
                CommonHelper::logException($exception);
            }
        }

        // Compiling all routes
        Router::compileAll();

        // HTTP verb tweak
        //
        // (This allows us to use any HTTP Verb if the form contains a hidden field
        // named "_method")
        if (isset($_SERVER['REQUEST_METHOD'])) {
            if (\strtolower($_SERVER['REQUEST_METHOD']) === 'post' && \array_key_exists('_method', $_POST)) {
                $_SERVER['REQUEST_METHOD'] = $_POST['_method'];
            }

            $requestMethod = $_SERVER['REQUEST_METHOD'];
        } else {
            $requestMethod = 'CLI';
        }

        // Getting the current url
        $url = self::currentUrl();

        try {
            $currentRoute = Router::getByUrl($url);
        } catch (RouteNotFoundException $exception) {
            $currentRoute = Router::{ !$isCli ? 'any' : 'cli' }($url, $config['route_404_override']);
            $currentRoute->is404 = true;
            $currentRoute->isCli = $isCli;
        }

        $currentRoute->requestMethod = $requestMethod;

        Router::setCurrentRoute($currentRoute);
    }

    private static function preController(array &$params, \CI_URI $URI, string &$class, string &$method): void
    {
        $route = Router::getCurrentRoute();

        // Is a 404 route? stop this hook
        if ($route->is404) {
            return;
        }

        $pcount = 0;

        // Removing controller's sub-directory limitation over "/" path
        if ($route->getFullPath() === '/') {
            if (!empty($route->getNamespace()) || \is_string($route->getAction())) {
                [$_class, $_method] = \explode('@', $route->getAction());
                $_controller = APPPATH . 'controllers/' . (!empty($route->getNamespace()) ? $route->getNamespace() . '/' : '') . $_class .'.php';

                if (\file_exists($_controller)) {
                    require_once $_controller;
                    [$class, $method] = \explode('@', $route->getAction());
                } else {
                    $route->setAction(fn () => self::triggerError());
                }
            }
        }

        if (!$route->isCli) {
            $params_result = [];
            $scount = 0;

            foreach (\explode('/', $route->getFullPath()) as $currentSegmentIndex => $segment) {
                $key = [];

                if (\preg_match('/\{(.*?)\}+/', $segment)) {
                    foreach ($route->params as $param) {
                        $keySegment = empty($key[$param->getSegmentIndex()])
                            ? \str_replace($param->getSegment(), '('.$param->getRegex().')', $param->getFullSegment())
                            : \str_replace($param->getSegment(), '('.$param->getRegex().')', $key[$param->getSegmentIndex()]);
                        $key[$param->getSegmentIndex()] = \str_replace(['((', '))'], ['(', ')'], $keySegment);
                    }

                    foreach ($route->params as $param) {
                        if ($param->segmentIndex === $currentSegmentIndex) {
                            $segment = \preg_replace('/\((.*)\):/', '', $segment);

                            if (\preg_match('#^'.$key[$currentSegmentIndex].'$#', $URI->segment($currentSegmentIndex+1), $matches)) {
                                if (isset($matches[$pcount + 1 - $scount])) {
                                    $route->params[$pcount]->value = $matches[$pcount + 1 - $scount];
                                }
                            }

                            if (\is_callable($route->getAction()) && !empty($URI->segment($currentSegmentIndex+1))) {
                                $params[$route->params[$pcount]->getName()] = $URI->segment($currentSegmentIndex+1);
                            }

                            // Removing "sticky" route parameters
                            if (!StringHelper::strStartsWith($param->getName(), '_')) {
                                $params[] = $route->params[$pcount]->value;
                            }

                            $pcount++;
                        }
                    }

                    $scount++;
                }
            }
        } else {
            if (!empty($route->params)) {
                $argv = \array_slice($_SERVER['argv'], 1);

                if ($argv) {
                    $params = \array_slice($argv, $route->paramOffset);
                }

                foreach ($route->params as $i => $param) {
                    $param->value = $params[$i] ?? null;
                }
            }
        }

        Router::setCurrentRoute($route);

        // If the current route is an anonymous route, we must prevent
        // the execution of their 'traditional' counterpart (if exists)
        if (\is_callable($route->getAction())) {
            $RTR = &load_class('Router', 'core');
            $config =& CommonHelper::apiConfig();
            $class = $config['route_fake_default_controller'];

            if (!\class_exists($class)) {
                require_once APPPATH.'controllers/'.  $class .'.php';
            }

            $method = 'index';
        }
    }

    private static function postControllerConstructor(array $params): void
    {
        $ci =& get_instance();
        // Current route configuration and dispatch
        $ci->route = Router::getCurrentRoute();

        if (!$ci->route->is404) {
            // Setting "sticky" route parameters values as default for current route
            foreach ($ci->route->params as $param) {
                if (StringHelper::strStartsWith($param->getName(), '_')) {
                    Router::setDefaultParam($param->getName(), $ci->route->param($param->getName()));
                }
            }
        }

        if (\is_callable($ci->route->getAction())) {
            \call_user_func_array($ci->route->getAction(), $params);
        }
    }

    private static function currentUrl(): string
    {
        if (is_cli()) {
            $args = \array_slice($_SERVER['argv'], 1);
            return $args ? \implode('/', $args) : '/';
        }

        $uriProtocol = config_item('uri_protocol');

        $removeRelativeDirectory = function ($uri) {
            $uris = [];
            $tok = \strtok($uri, '/');

            while ($tok !== false) {
                if ((!empty($tok) || $tok === '0') && $tok !== '..') {
                    $uris[] = $tok;
                }

                $tok = \strtok('/');
            }

            return \implode('/', $uris);
        };

        $parseRequestUri = function () use ($removeRelativeDirectory) {
            $uri = \parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
            $uri = $uri['path'] ?? '';
            $query = $uri['query'] ?? '';

            if (isset($_SERVER['SCRIPT_NAME'][0])) {
                if (StringHelper::strStartsWith($uri, $_SERVER['SCRIPT_NAME'])) {
                    $uri = \substr($uri, \strlen($_SERVER['SCRIPT_NAME']));
                } elseif (StringHelper::strStartsWith($uri, \dirname($_SERVER['SCRIPT_NAME']))) {
                    $uri = \substr($uri, \strlen(\dirname($_SERVER['SCRIPT_NAME'])));
                }
            }

            if (\trim($uri, '/') === '' && \strncmp($query, '/', 1) === 0) {
                $query = \explode('?', $query, 2);
                $uri = $query[0];
                $_SERVER['QUERY_STRING'] = $query[1] ?? '';
            } else {
                $_SERVER['QUERY_STRING'] = $query;
            }

            \parse_str($_SERVER['QUERY_STRING'], $_GET);

            if ($uri === '/' || $uri === '') {
                $uri = '/';
            }

            return $removeRelativeDirectory($uri);
        };

        if ($uriProtocol === 'REQUEST_URI') {
            $url = $parseRequestUri();
        } elseif ($uriProtocol === 'QUERY_STRING') {
            $uri = $_SERVER['QUERY_STRING'] ?? @\getenv('QUERY_STRING');

            if (\trim($uri, '/') === '') {
                $uri = '';
            } elseif (\strncmp($uri, '/', 1) === 0) {
                $uri = \explode('?', $uri, 2);
                $_SERVER['QUERY_STRING'] = $uri[1] ?? '';
                $uri = $uri[0];
            }

            \parse_str($_SERVER['QUERY_STRING'], $_GET);

            $url = $removeRelativeDirectory($uri);
        } elseif ($uriProtocol === 'PATH_INFO') {
            $url = \array_key_exists('PATH_INFO', $_SERVER) ? \trim($_SERVER['PATH_INFO']) : $parseRequestUri();
        } else {
            show_error('Unsupported uri protocol', 500, 'Boot error');
        }

        return empty($url) ? '/' : $url;
    }

    private static function triggerError()
    {
        if (is_cli() === true) {
            show_404('', false);
        }

        $error_404 = Router::getError404();

        if (\is_null($error_404) || !\is_callable($error_404)) {
            show_404('', false);
        }

        if (\is_callable($error_404)) {
            \call_user_func($error_404);
            exit();
        }
    }
}
