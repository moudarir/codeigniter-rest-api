<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Exception;
use Moudarir\CodeigniterApi\Models\Api\ApiKey;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Server extends CI_Controller
{

    /**
     * The start of the response time from the server.
     *
     * @var float
     */
    private float $response_start_time;

    /**
     * @var array
     */
    private array $api_config;

    /**
     * @var array|null
     */
    private ?array $auth_data = null;

    /**
     * @var Request
     */
    private static Request $request;

    /**
     * @var Response
     */
    private static Response $response;

    /**
     * @var Logger|null
     */
    private ?Logger $logger = null;

    /**
     * @var ApiKey|null
     */
    private ?ApiKey $api_key = null;

    /**
     * Server constructor.
     *
     * @param string $config_filename ex.: 'rest-api.php' is passed as 'rest-api' without '.php'.
     *                                The filename is used to load config and language
     */
    public function __construct(string $config_filename = 'rest-api')
    {
        parent::__construct();
        load_class('Model', 'core');

        // Load the configuration
        $this->setApiConfig($config_filename);

        // Load the language file
        $this->lang->load($config_filename);

        // Force the use of HTTPS for REST API calls
        if (is_https() === false) {
            (new Response($this->api_config))->forbidden($this->lang->line('rest_https_protocol_required'));
        }

        // Don't try to parse template variables like {elapsed_time} and {memory_usage}
        // when output is displayed for not damaging data accidentally
        $this->output->parse_exec_vars = false;

        // Log the loading time to the log table
        if ($this->api_config['enable_logging'] === true) {
            // Start the timer for how long the request takes
            $this->response_start_time = microtime(true);
        }

        if ($this->api_config['enable_logging'] === true) {
            $this->logger = new Logger();
        }

        if (!isset(self::$request)) {
            try {
                if (!isset(self::$request)) {
                    self::$request = new Request($this->api_config);
                }

                if (!isset(self::$response)) {
                    self::$response = new Response($this->api_config, self::$request ?? null, $this->logger);
                }

                // Checking for keys? GET TO WorK!
                if ($this->api_config['enable_authentication'] === true) {
                    $auth = new Authorization($this->api_config, self::$request);
                    $auth->check();

                    // They provided a key, but it wasn't valid, so get them out of here
                    if ($auth->isAuthorized() === false) {
                        if ($this->api_config['enable_logging']) {
                            $data = [
                                'key_id' => $auth->getApiKey() !== null ? $auth->getApiKey()->getId() : null,
                                'method' => self::getRequest()->getMethod(),
                                'authorized' => 0,
                            ];
                            $this->logger->add($data);
                        }

                        // fix cross site to option request error
                        if (self::getRequest()->getMethod() === 'options') {
                            exit();
                        }

                        self::getResponse()->unauthorized();
                    } else {
                        $this->api_key = $auth->getApiKey();
                        $this->auth_data = $auth->getAuthData();

                        if ($this->api_config['enable_logging']) {
                            $data = [
                                'key_id' => $auth->getApiKey() !== null ? $auth->getApiKey()->getId() : null,
                                'method' => self::getRequest()->getMethod(),
                                'authorized' => 1,
                            ];
                            $this->logger->add($data);
                        }
                    }
                }
            } catch (Exception $e) {
                (new Response($this->api_config))->response([
                    $this->api_config['error_field_name']   => true,
                    $this->api_config['message_field_name'] => $e->getMessage()
                ], $e->getCode());
            }
        }
    }

    /**
     * De-constructor.
     *
     * @return void
     */
    public function __destruct()
    {
        // Log the loading time to the log table
        if ($this->api_config['enable_logging'] === true && $this->logger !== null) {
            $responseTime = microtime(true) - $this->response_start_time;
            $this->logger->update(['response_time' => $responseTime]);
        }
    }

    /**
     * Requests are not made to methods directly, the request will be for
     * an "object". This simply maps the object and method to the correct
     * Controller method.
     *
     * @param string $object_called
     * @param array $arguments The arguments passed to the controller method
     * @throws Exception
     */
    public function mapping(string $object_called, array $arguments = [])
    {
        // Remove the supported format from the function name e.g. index.json => index
        $pattern = '/^(.*)\.(?:'.implode('|', array_keys($this->api_config['supported_formats'])).')$/';
        $object_called = preg_replace($pattern, '$1', $object_called);

        $method = self::getRequest()->getMethod();
        $controllerMethod = Helpers::stringToCamelcase($object_called . '_' . $method);

        // Does this method exist? If not, try executing an index method
        if (!method_exists($this, $controllerMethod)) {
            $controllerMethod = Helpers::stringToCamelcase('index_' . $method);
            array_unshift($arguments, $object_called);
        }

        // Sure it exists, but can they do anything with it?
        if (!method_exists($this, $controllerMethod)) {
            self::getResponse()->methodNotAllowed($this->lang->line('rest_unknown_path'));
        }

        // Call the controller method and passed arguments
        call_user_func_array([$this, $controllerMethod], $arguments);
    }

    /**
     * @return ApiKey|null
     */
    public function getApiKey(): ?ApiKey
    {
        return $this->api_key ?? null;
    }

    /**
     * @return array
     */
    public function getApiConfig(): array
    {
        return $this->api_config;
    }

    /**
     * @return array|null
     */
    public function getAuthData(): ?array
    {
        return $this->auth_data;
    }

    /**
     * @return Request
     */
    public static function getRequest(): Request
    {
        return self::$request;
    }

    /**
     * @return Response
     */
    public static function getResponse(): Response
    {
        return self::$response;
    }

    /**
     * Retrieve a value from a GET request
     *
     * @param string|null $key Key to retrieve from the GET request. If null an array of arguments is returned
     * @param bool $xssClean   Whether to apply XSS filtering
     * @return array|string|null Value from the GET request; otherwise, null
     */
    public function get(?string $key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getGetArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from a OPTIONS request
     *
     * @param string|null $key Key to retrieve from the OPTIONS request. If null an array of arguments is returned
     * @param bool $xssClean   Whether to apply XSS filtering
     * @return array|string|null Value from the OPTIONS request; otherwise, null
     */
    public function options(?string $key = null, bool $xssClean = false)
    {
        $args = self::getRequest()->getOptionsArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from a HEAD request
     *
     * @param null $key      Key to retrieve from the HEAD request If null an array of arguments is returned
     * @param bool $xssClean Whether to apply XSS filtering
     * @return array|string|null Value from the HEAD request; otherwise, null
     */
    public function head($key = null, bool $xssClean = false)
    {
        $args = self::getRequest()->getHeadArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from a POST request
     *
     * @param mixed|null $key      Key to retrieve from the POST request
     *                       If null an array of arguments is returned
     * @param bool $xssClean Whether to apply XSS filtering
     * @return array|string|null Value from the POST request; otherwise, null
     */
    public function post($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getPostArgs();
        if ($key === null) {
            if (!empty($args)) {
                $mode = RecursiveIteratorIterator::CATCH_GET_CHILD;
                foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($args), $mode) as $key => $value) {
                    $args[$key] = $this->xssClean($args[$key], $xssClean);
                }
            }

            return $args;
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from a PUT request
     *
     * @param null $key      Key to retrieve from the PUT request If null an array of arguments is returned
     * @param bool $xssClean Whether to apply XSS filtering
     * @return array|string|null Value from the PUT request; otherwise, null
     */
    public function put($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getPutArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from a DELETE request
     *
     * @param null $key      Key to retrieve from the DELETE request If null an array of arguments is returned
     * @param bool $xssClean Whether to apply XSS filtering
     * @return array|string|null Value from the DELETE request; otherwise, null
     */
    public function delete($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getDeleteArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from a PATCH request
     *
     * @param null $key      Key to retrieve from the PATCH request. If null an array of arguments is returned
     * @param bool $xssClean Whether to apply XSS filtering
     * @return array|string|null Value from the PATCH request; otherwise, null
     */
    public function patch($key = null, bool $xssClean = false)
    {
        $args = self::getRequest()->getPatchArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Retrieve a value from the query parameters
     *
     * @param null $key      Key to retrieve from the query parameters If null an array of arguments is returned
     * @param bool $xssClean Whether to apply XSS filtering
     * @return array|string|null Value from the query parameters; otherwise, null
     */
    public function query($key = null, bool $xssClean = true)
    {
        $args = self::getRequest()->getQueryArgs();
        if ($key === null) {
            return $this->xssClean($args, $xssClean);
        }

        return array_key_exists($key, $args) ? $this->xssClean($args[$key], $xssClean) : null;
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented
     *
     * @param mixed $value        Input data
     * @param bool|null $xssClean Whether to apply XSS filtering
     * @return mixed
     */
    protected function xssClean($value, ?bool $xssClean = null)
    {
        is_bool($xssClean) || $xssClean = $this->api_config['xss_filtering'];

        return $xssClean === true ? $this->security->xss_clean($value) : $value;
    }

    /**
     * @param string $filename
     * @return void
     */
    private function setApiConfig(string $filename = 'rest-api'): void
    {
        static $config;

        if (empty($config)) {
            if (file_exists(APPPATH . 'config/' . $filename . '.php')) {
                include(APPPATH . 'config/' . $filename . '.php');
            } else {
                $config = [];
            }

            if (empty($config)) {
                if (file_exists(APPPATH . 'config/' . ENVIRONMENT . '/' . $filename . '.php')) {
                    include(APPPATH . 'config/' . ENVIRONMENT . '/' . $filename . '.php');
                } else {
                    if (file_exists(__DIR__.'/'.$filename.'.php')) {
                        include __DIR__.'/'.$filename.'.php';
                    }
                }
            }
        }

        $this->api_config = $config;
    }
}
