<?php

namespace Moudarir\CodeigniterApi\Routes;

use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use function is_cli;
use function log_message;
use function show_error;

class Route
{

    private array $config;

    private string $path;

    private string $fullPath;

    private string $name;

    /**
     * @var string[]
     */
    private array $methods = [];

    /**
     * @var string|callable
     */
    private $action;

    private string $namespace = '';

    private string $prefix = '';

    /**
     * @var RouteParam[]
     */
    public array $params = [];

    public ?int $paramOffset = null;

    public ?int $optionalParamOffset = null;

    private bool $hasOptionalParams = false;

    public bool $is404 = false;

    public bool $isCli = false;

    public string $requestMethod;

    const HTTP_VERBS = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD'];

    /**
     * @param array|string $methods
     * @param array $args
     */
    public function __construct($methods, array $args)
    {
        $this->config =& CommonHelper::apiConfig();
        if (\is_string($methods)) {
            $methods = $methods === 'ANY' ? self::HTTP_VERBS : [\strtoupper($methods)];
        } else {
            \array_shift($args);
        }

        foreach ($methods as $method) {
            $this->methods[] = \strtoupper($method);
        }

        // Required route attributes
        [$path, $action] = $args;
        $this->path = \trim($path, '/') === '' ? '/' : \trim($path, '/');

        if (!\is_callable($action) && \count(\explode('@', $action)) !== 2) {
            log_message('error', "Route action must be in 'controller@method' syntax or be a valid callback.");
            exit(1);
        }

        $this->action = $action;
        $attributes = isset($args[2]) && \is_array($args[2]) ? $args[2] : null;
        $context = Router::getContext();

        // Route group inherited attributes
        if (!empty($context['prefix'])) {
            foreach ($context['prefix'] as $prefix) {
                $this->prefix .= \trim($prefix, '/') !== '' ? '/' .\trim($prefix, '/') : '';
            }

            $this->prefix = \trim($this->prefix, '/');
        }

        if (!empty($context['namespace'])) {
            foreach ($context['namespace'] as $namespace) {
                $this->namespace .= \trim($namespace, '/') != '' ? '/' .\trim($namespace, '/') : '';
            }

            $this->namespace = \trim($this->namespace, '/');
        }

        // Optional route attributes
        if ($attributes !== null) {
            if (\array_key_exists('namespace', $attributes)) {
                $this->namespace = (!empty($this->namespace) ? '/' : '' ) . \trim($attributes['namespace'], '/');
            }

            if (\array_key_exists('prefix', $attributes)) {
                $this->prefix .= (!empty($this->prefix) ? '/' : '' ) . \trim($attributes['prefix'], '/');
            }
        }

        // Parsing route parameters
        $_names = [];
        $fullPath = \trim($this->prefix, '/') !== '' ? $this->prefix . '/' . $this->path : $this->path;
        $fullPath = \trim($fullPath, '/') === '' ? '/' : \trim($fullPath, '/');

        foreach (\explode('/', $fullPath) as $i => $segment) {
            if (\preg_match_all('/\{(.*?)\}+/', $segment, $matches)) {
                if ($this->paramOffset === null) {
                    $this->paramOffset = $i;
                }

                foreach ($matches[0] as $paramCode) {
                    $param = new RouteParam($paramCode, $i, $segment);

                    if (\in_array($param->getName(), $_names)) {
                        show_error('Duplicate route parameter <strong>' . $param->getName() . '</strong> in route <strong>"' .  $this->path . '</strong>"');
                    }

                    $_names[] = $param->getName();

                    if ($param->isOptional()) {
                        $this->hasOptionalParams = true;
                        if ($this->optionalParamOffset === null) {
                            $this->optionalParamOffset = $i;
                        }
                    } else {
                        if ($this->hasOptionalParams) {
                            show_error('Required <strong>' . $param->getName() . '</strong> route parameter is not allowed at this position in <strong>"' . $this->path . '"</strong> route');
                        }
                    }

                    $this->params[] = $param;
                }
            }
        }

        // Automatically set the default controller if the path is "/"
        if ($fullPath === '/' && \in_array('GET', $this->methods)) {
            Router::$compiled['reserved']['default_controller'] = \is_string($action)
                ? (empty($this->namespace)
                    ? \str_ireplace('@', '/', $action)
                    : $this->config['route_fake_default_controller'])
                :  $this->config['route_fake_default_controller'];
        }

        $this->fullPath = $fullPath;
        $this->isCli = is_cli();
    }

    /**
     * Compiles route to a CodeIgniter native route
     */
    public function compile(): array
    {
        $routes = [];

        foreach ($this->methods as $method) {
            $path = $this->fullPath;

            foreach ($this->params as $param) {
                $path = \str_ireplace($param->getSegment(), $param->getPlaceholder(), $path);
            }

            $pCount = 0;

            if (\is_callable($this->action)) {
                $target = $this->config['route_fake_default_controller'];
                $baseTarget = $target;
            } else {
                $baseTarget = (!empty($this->namespace) ? $this->namespace . '/' : '')
                    . \str_ireplace('@', '/', $this->action);

                $target = $baseTarget;

                foreach ($this->params as $c => $param) {
                    $target .= '/$' . ($c + 1);
                    if (!$param->isOptional()) {
                        $baseTarget .= '/$'. ($c + 1);
                        $pCount++;
                    }
                }
            }

            // Fallback routes
            if ($this->optionalParamOffset !== null) {
                $segments = \explode('/', $path);
                $sCount = \count($segments);
                $basePath = \implode('/', \array_slice($segments, 0, $this->optionalParamOffset));
                $routes[][$basePath][$method] = $baseTarget;

                for ($i = $this->optionalParamOffset; $i < $sCount; $i++) {
                    $basePath .= '/' . $segments[$i];
                    if (\is_string($this->action)) {
                        $baseTarget .= '/$' . ++$pCount;
                    }
                    $routes[][$basePath][$method] = $baseTarget;
                }
            }

            // Main route
            $routes[][$path][$method] = $target;
        }

        return $routes;
    }

    /**
     * Gets or sets a route parameter
     */
    public function param(string $name, ?string $value = null): ?string
    {
        foreach ($this->params as $param) {
            if ($name === $param->getName()) {
                if ($value !== null) {
                    $param->value = $value;
                }

                return $param->value;
            }
        }

        return null;
    }

    /**
     * Checks if the route has a specific parameter
     */
    public function hasParam(string $name): bool
    {
        foreach ($this->params as $param) {
            if ($name === $param->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $params
     * @throws \Exception
     */
    public function buildPath($params): string
    {
        $defaults = Router::getDefaultParams();

        if (\is_object($params)) {
            $params = (array)$params;
        }

        if (!\is_array($params)) {
            if (!empty($params) && \count($this->params) === 1) {
                $params = [$this->params[0]->getName() => $params];
            } else {
                $params = [];
            }
        }

        $path = $this->getPrefix() . '/' . $this->getPath();
        $skippedOptional = null;

        foreach ($this->params as $param) {
            $isMissingRequiredField = !$param->isOptional() && !isset($defaults[$param->getName()]) && !isset($params[$param->getName()]);
            $alreadySkippedOptionalField = $param->isOptional() && $skippedOptional !== null && (isset($defaults[$param->getName()]) || isset($params[$param->getName()]));

            if ($isMissingRequiredField || $alreadySkippedOptionalField) {
                throw new \Exception('Missing "' . ($skippedOptional === null ? $param->getName() : $skippedOptional) . '" parameter for "' . $this->getName() . '" route');
            }

            if (isset($defaults[$param->getName()])) {
                $param->value = $defaults[$param->getName()];
            }

            if (isset($params[$param->getName()])) {
                $param->value = $params[$param->getName()];
            }

            if (isset($defaults[$param->getName()]) || isset($params[$param->getName()])) {
                $path = \str_replace($param->getSegment(), $param->value, $path);
            } else {
                $skippedOptional = $skippedOptional === null ? $param->getName() : $skippedOptional;
                $_path = \explode('/', $path);
                unset($_path[\array_search($param->getSegment(), $_path)]);
                $path  = \implode('/', $_path);
            }
        }

        return \trim($path, '/');
    }

    /**
     * Sets route name
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets route name
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * Gets route path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets route full path
     */
    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    /**
     * Sets route path
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Gets route prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Gets route action
     *
     * @return callable|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets route action
     *
     * @param callable|string $action
     */
    public function setAction($action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Gets route namespace
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Gets route accepted HTTP Verbs
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}
