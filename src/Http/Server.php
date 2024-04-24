<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Moudarir\CodeigniterApi\Exceptions\AuthenticationException;
use Moudarir\CodeigniterApi\Exceptions\FormatException;
use Moudarir\CodeigniterApi\Exceptions\RequestException;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use function is_https;
use function load_class;

class Server extends CI_Controller
{

    private float $response_start_time;

    private array $api_config;

    private ?array $auth_data = null;

    private static Request $request;

    private static Response $response;

    private ?Logger $logger = null;

    private ?array $api_key = null;

    protected string $auth_method = 'auto';

    public function __construct()
    {
        parent::__construct();
        load_class('Model', 'core');

        $this->setApiConfig();
        $this->lang->load('rest-api-server');

        if (is_https() === false) {
            (new Response($this->api_config))->forbidden($this->lang->line('rest_https_protocol_required'));
        }

        // Don't try to parse template variables like {elapsed_time} and {memory_usage}
        $this->output->parse_exec_vars = false;

        if ($this->api_config['enable_logging'] === true) {
            $this->response_start_time = \microtime(true);
            $this->logger = new Logger();
        }

        if (!isset(self::$request)) {
            try {
                self::$request = new Request($this->api_config);
            } catch (RequestException|FormatException $exception) {
                (new Response($this->api_config))->response([
                    $this->api_config['error_field_name'] => true,
                    $this->api_config['message_field_name'] => $exception->getMessage()
                ], $exception->getCode());
            }
        }

        if (!isset(self::$response)) {
            self::$response = new Response($this->api_config, self::$request, $this->logger);
        }
    }

    public function __destruct()
    {
        if ($this->api_config['enable_logging'] === true && $this->logger !== null) {
            $this->logger->push(['response_time' => (\microtime(true) - $this->response_start_time)])->flush();
        }
    }

    public function _remap(string $called_method, array $arguments = [])
    {
        $this->mapping($called_method, $arguments);
    }

    /**
     * @used-by _remap()
     */
    public function mapping(string $called_method, array $arguments = [])
    {
        $method = self::getRequest()->getMethod();

        // Sure it exists, but can they do anything with it?
        if (!\method_exists($this, $called_method)) {
            if ($this->api_config['enable_logging'] === true) {
                $this->logger->push([
                    'key_id' => null,
                    'method' => $method,
                    'authorized' => 0,
                ]);
            }

            self::getResponse()->methodNotAllowed($this->lang->line('rest_unknown_path'));
        }

        if ($this->api_config['enable_authentication'] === true) {
            try {
                $auth = new Authorization($this->api_config, self::$request);
                $auth->check($this->auth_method);
                $this->api_key = $auth->getApiKey();
                $this->auth_data = $auth->getAuthData();
                $authorized = true;

                if ($this->api_config['enable_limits'] === true) {
                    $authorized = $auth->checkLimits($called_method);
                }

                if ($this->api_config['enable_logging'] === true) {
                    $this->logger->push([
                        'key_id' => $auth->getApiKey() !== null ? $auth->getApiKey()['id'] : null,
                        'method' => $method,
                        'authorized' => $authorized === true ? 1 : 0,
                    ]);
                }

                if ($authorized === false) {
                    self::getResponse()->unauthorized($this->lang->line('rest_api_request_limits_exceeded'));
                }
            } catch (AuthenticationException $exception) {
                if ($this->api_config['enable_logging'] === true) {
                    $this->logger->push([
                        'key_id' => null,
                        'method' => $method,
                        'authorized' => 0,
                    ]);
                }

                // fix cross site to option request error
                if ($method === 'options') {
                    exit();
                }

                self::getResponse()->unauthorized($exception->getMessage());
            }
        }

        \call_user_func_array([$this, $called_method], $arguments);
    }

    public function getApiKey(): ?array
    {
        return $this->api_key ?? null;
    }

    public function getApiConfig(): array
    {
        return $this->api_config;
    }

    public function getAuthData(): ?array
    {
        return $this->auth_data;
    }

    public static function getRequest(): Request
    {
        return self::$request;
    }

    public static function getResponse(): Response
    {
        return self::$response;
    }

    public function get(?string $key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getGetArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function options(?string $key = null, bool $xssClean = false)
    {
        $args = self::getRequest()->getOptionsArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function head($key = null, bool $xssClean = false)
    {
        $args = self::getRequest()->getHeadArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function post($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getPostArgs();
        if ($key === null) {
            if (!empty($args)) {
                $mode = \RecursiveIteratorIterator::CATCH_GET_CHILD;
                foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($args), $mode) as $key => $value) {
                    $args[$key] = $this->xssClean($args[$key], $xssClean);
                }
            }

            return $args;
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function put($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getPutArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function delete($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getDeleteArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function patch($key = null, bool $xssClean = false)
    {
        $args = self::getRequest()->getPatchArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function query($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getQueryArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return \array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    public function body()
    {
        return self::getRequest()->getBody();
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented
     *
     * @param mixed $value Input data
     * @param bool|null $xssClean Whether to apply XSS filtering
     * @return mixed
     */
    protected function xssClean($value, ?bool $xssClean = null)
    {
        \is_bool($xssClean) || $xssClean = $this->api_config['xss_filtering'];
        return $xssClean === true ? $this->security->xss_clean($value) : $value;
    }

    private function setApiConfig(): void
    {
        $config =& CommonHelper::apiConfig();
        $this->api_config = $config;
    }
}
