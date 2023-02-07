<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Exception;

class Request
{

    /**
     * @var CI_Controller
     */
    private CI_Controller $ci;

    /**
     * @var array
     */
    private array $config;

    /**
     * @var string|null
     */
    private ?string $method;

    /**
     * @var string|null
     */
    private ?string $input_format;

    /**
     * @var string|null
     */
    private ?string $output_format;

    /**
     * @var array|string|bool|null
     */
    private $body = null;

    /**
     * The arguments from GET, POST, PUT, DELETE, PATCH, HEAD and OPTIONS request methods combined.
     *
     * @var array
     */
    private array $args = [];

    /**
     * The arguments for the query parameters.
     *
     * @var array|null
     */
    private ?array $query_args = [];

    /**
     * The arguments for the GET request method.
     *
     * @var array
     */
    private array $get_args = [];

    /**
     * The arguments for the POST request method.
     *
     * @var array
     */
    private array $post_args = [];

    /**
     * The arguments for the PUT request method.
     *
     * @var array
     */
    private array $put_args = [];

    /**
     * The arguments for the DELETE request method.
     *
     * @var array
     */
    private array $delete_args = [];

    /**
     * The arguments for the PATCH request method.
     *
     * @var array
     */
    private array $patch_args = [];

    /**
     * The arguments for the HEAD request method.
     *
     * @var array
     */
    private array $head_args = [];

    /**
     * The arguments for the OPTIONS request method.
     *
     * @var array
     */
    private array $options_args = [];

    /**
     * Request constructor.
     *
     * @param array $config Rest Api Configuration
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->ci = &get_instance();
        $this->config = $config;

        // Check to see if the current IP address is blacklisted
        $this->blacklistedIpCheck();

        // How is this request being made? GET, POST, PATCH, DELETE, PUT, HEAD or OPTIONS
        $this->setMethod();

        // Check for CORS access request
        if ($this->config['enable_cors_check'] === true) {
            $this->checkCORS();
        }

        // Create an argument container if it doesn't exist e.g. get_args
        $methodArgs = $this->getMethod() . '_args';
        $setMethod = 'set' . $this->getMethod(true);
        if (isset($this->$methodArgs) === false) {
            $this->$methodArgs = [];
        }

        // Set up the query parameters
        $this->setQueryArgs();

        // Set up the GET variables
        $this->setGet($this->ci->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->setInputFormat();

        $this->$setMethod();

        // Fix parse method return arguments null
        if ($this->$methodArgs === null) {
            $this->$methodArgs = [];
        }

        // Which format should the data be returned in?
        $this->output_format = $this->detectOutputFormat();

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->getInputFormat() !== null && $this->body) {
            $this->body = Format::factory($this->body, $this->getInputFormat())->toArray();

            // Assign payload arguments to proper method container
            $this->$methodArgs = $this->body;
        }

        //get header vars
        $this->setHead(true);

        $this->setArgs($this->$methodArgs);

        // Allow only ajax requests
        if ($this->ci->input->is_ajax_request() === false && $this->config['allow_ajax_only']) {
            throw new Exception($this->ci->lang->line('rest_allow_ajax_only'), Config::HTTP_NOT_ACCEPTABLE);
        }

        // If whitelist is enabled it has the first chance to kick them out
        if ($this->config['enable_ip_whitelist']) {
            $this->whitelistIpCheck();
        }
    }

    /**
     * @param bool $ucFirst
     * @return string|null
     */
    public function getMethod(bool $ucFirst = false): ?string
    {
        if ($this->method === null || $ucFirst === false) {
            return $this->method;
        }

        return ucfirst($this->method);
    }

    /**
     * @return string|null
     */
    public function getInputFormat(): ?string
    {
        return $this->input_format;
    }

    /**
     * @return mixed|null|string Output format
     */
    public function getOutputFormat(): ?string
    {
        return $this->output_format;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getGetArgs(): array
    {
        return $this->get_args;
    }

    /**
     * @return array
     */
    public function getPostArgs(): array
    {
        return $this->post_args;
    }

    /**
     * @return array
     */
    public function getPutArgs(): array
    {
        return $this->put_args;
    }

    /**
     * @return array
     */
    public function getPatchArgs(): array
    {
        return $this->patch_args;
    }

    /**
     * @return array
     */
    public function getDeleteArgs(): array
    {
        return $this->delete_args;
    }

    /**
     * @return array
     */
    public function getHeadArgs(): array
    {
        return $this->head_args;
    }

    /**
     * @return array
     */
    public function getOptionsArgs(): array
    {
        return $this->options_args;
    }

    /**
     * @return array|null
     */
    public function getQueryArgs(): ?array
    {
        return $this->query_args;
    }

    /**
     * Setters
     */

    /**
     * Get the HTTP request method e.g. get or post.
     * Supported request method as a lowercase string;
     * otherwise, NULL if not supported
     */
    private function setMethod()
    {
        // Declare a variable to store the method
        $method = null;

        // Determine whether the ENABLE_EMULATE_REQUEST setting is enabled
        if ($this->config['enable_emulate_request'] === true) {
            $method = $this->ci->input->post('_method');
            if ($method === null) {
                $method = $this->ci->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }

            $method = strtolower($method);
        }

        if (empty($method)) {
            // Get the request method as a lowercase string
            $method = $this->ci->input->method();
        }

        if (in_array($method, $this->config['allowed_http_methods']) && method_exists($this, 'set' . ucfirst($method))) {
            $this->method = $method;
        } else {
            $this->method = 'get';
        }
    }

    /**
     * @return void
     */
    private function setInputFormat()
    {
        // Get the CONTENT-TYPE value from the SERVER variable
        $contentType = $this->ci->input->server('CONTENT_TYPE');

        if (!empty($contentType)) {
            // If a semi-colon exists in the string, then explode by ; and get the value of where
            // the current array pointer resides. This will generally be the first element of the array
            $contentType = (strpos($contentType, ';') !== false ? current(explode(';', $contentType)) : $contentType);

            // Check all formats against the CONTENT-TYPE header
            foreach ($this->config['supported_formats'] as $type => $mime) {
                // $type = format e.g. csv
                // $mime = mime type e.g. application/csv

                // If both the mime types match, then return the format
                if ($contentType === $mime) {
                    $this->input_format = $type;
                    break;
                }
            }
        }

        $this->input_format = null;
    }

    /**
     * Detect which format should be used to output the data.
     *
     * @return string|null
     */
    private function detectOutputFormat(): ?string
    {
        $formats = $this->config['supported_formats'];
        // Concatenate formats to a regex pattern e.g. \.(csv|json|xml)
        $pattern = '/\.(' . implode('|', array_keys($formats)) . ')($|\/)/';
        $matches = [];

        // Check if a file extension is used e.g. http://example.com/api/index.json?param1=param2
        if (preg_match($pattern, $this->ci->uri->uri_string(), $matches)) {
            return $matches[1];
        }

        // Get the format parameter named as 'format'
        if (array_key_exists('format', $this->getGetArgs())) {
            $format = strtolower($this->getGetArgs()['format']);

            if (array_key_exists($format, $formats)) {
                return $format;
            }
        }

        // Get the HTTP_ACCEPT server variable
        $http_accept = $this->ci->input->server('HTTP_ACCEPT');

        // Otherwise, check the HTTP_ACCEPT server variable
        if ($this->config['ignore_http_accept'] === false && $http_accept !== null) {
            // Check all formats against the HTTP_ACCEPT header
            foreach (array_keys($formats) as $format) {
                // Has this format been requested?
                if (strpos($http_accept, $format) !== false) {
                    if ($format !== 'html' && $format !== 'xml') {
                        // If not HTML or XML assume it's correct
                        return $format;
                    } elseif ($format === 'html' && strpos($http_accept, 'xml') === false) {
                        // HTML or XML have shown up as a match
                        // If it is truly HTML, it wont want any XML
                        return $format;
                    } elseif ($format === 'xml' && strpos($http_accept, 'html') === false) {
                        // If it is truly XML, it wont want any HTML
                        return $format;
                    }
                }
            }
        }

        return $this->config['default_output_format'];
    }

    /**
     * Merge both for one mega-args variable
     *
     * @param array $extra
     * @return void
     */
    private function setArgs(array $extra = [])
    {
        // Merge both for one mega-args variable
        $this->args = array_merge(
            $this->getGetArgs(),
            $this->getOptionsArgs(),
            $this->getPatchArgs(),
            $this->getHeadArgs(),
            $this->getPutArgs(),
            $this->getPostArgs(),
            $this->getDeleteArgs(),
            $extra
        );
    }

    /**
     * Parse the GET request arguments.
     *
     * @param array $merge_with
     * @return void
     */
    private function setGet(array $merge_with = [])
    {
        if (!empty($merge_with)) {
            $this->get_args = array_merge($this->get_args, $merge_with);
        } else {
            // Merge both the URI segments and query parameters
            $this->get_args = array_merge($this->get_args, $this->getQueryArgs());
        }
    }

    /**
     * Parse the POST request arguments.
     *
     * @return void
     */
    private function setPost()
    {
        $this->post_args = $_POST;

        if ($this->getInputFormat() !== null) {
            $this->body = $this->ci->input->raw_input_stream;
        }
    }

    /**
     * Parse the PUT request arguments.
     *
     * @return void
     */
    private function setPut()
    {
        if ($this->getInputFormat() !== null) {
            $this->body = $this->ci->input->raw_input_stream;
            if ($this->getInputFormat() === 'json') {
                $this->put_args = json_decode($this->ci->input->raw_input_stream);
            }
        } elseif ($this->getMethod() === 'put') {
            // If no file type is provided, then there are probably just arguments
            $this->put_args = $this->ci->input->input_stream();
        }
    }

    /**
     * Parse the HEAD request arguments.
     *
     * @param bool $manual
     * @return void
     */
    private function setHead(bool $manual = false)
    {
        if ($manual === true) {
            $this->head_args = array_merge($this->head_args, $this->ci->input->request_headers());
        } else {
            // Parse the HEAD variables
            parse_str(parse_url($this->ci->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);

            // Merge both the URI segments and HEAD params
            $this->head_args = array_merge($this->head_args, $head);
        }
    }

    /**
     * Parse the OPTIONS request arguments.
     *
     * @return void
     */
    private function setOptions()
    {
        // Parse the OPTIONS variables
        parse_str(parse_url($this->ci->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);

        // Merge both the URI segments and OPTIONS params
        $this->options_args = array_merge($this->options_args, $options);
    }

    /**
     * Parse the PATCH request arguments.
     *
     * @return void
     */
    private function setPatch()
    {
        // It might be a HTTP body
        if ($this->getInputFormat() !== null) {
            $this->body = $this->ci->input->raw_input_stream;
        } elseif ($this->getMethod() === 'patch') {
            // If no file type is provided, then there are probably just arguments
            $this->patch_args = $this->ci->input->input_stream();
        }
    }

    /**
     * Parse the DELETE request arguments.
     *
     * @return void
     */
    private function setDelete()
    {
        // These should exist if a DELETE request
        if ($this->getMethod() === 'delete') {
            $this->delete_args = $this->ci->input->input_stream();
        }
    }

    /**
     * Parse the query parameters
     *
     * @return void
     */
    private function setQueryArgs(): void
    {
        $this->query_args = $this->ci->input->get();
    }

    /**
     * Checks if the client's ip is in the 'ip_blacklist' config and generates a 401 response
     *
     * @return void
     * @throws Exception
     */
    private function blacklistedIpCheck()
    {
        if ($this->config['enable_ip_blacklist']) {
            // Match an ip address in a blacklist e.g. 127.0.0.0, 0.0.0.0
            $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->ci->input->ip_address());

            // Returns 1, 0 or FALSE (on error only). Therefore implicitly convert 1 to TRUE
            if (preg_match($pattern, $this->config['ip_blacklist'])) {
                throw new Exception($this->ci->lang->line('rest_ip_blacklist'), Config::HTTP_UNAUTHORIZED);
            }
        }
    }

    /**
     * Check if the client's ip is in the 'ip_whitelist' config and generates a 401 response.
     *
     * @return void
     * @throws Exception
     */
    private function whitelistIpCheck()
    {
        $whitelist = explode(',', $this->config['ip_whitelist']);

        array_push($whitelist, '127.0.0.1', '::1');

        foreach ($whitelist as &$ip) {
            // As $ip is a reference, trim leading and trailing whitespace, then store the new value
            // using the reference
            $ip = trim($ip);
        }

        if (in_array($this->ci->input->ip_address(), $whitelist) === false) {
            throw new Exception($this->ci->lang->line('rest_ip_whitelist'), Config::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Checks allowed domains, and adds appropriate headers for HTTP access control (CORS)
     *
     * @return void
     */
    private function checkCORS(): void
    {
        $allowedHeaders = implode(', ', $this->config['cors_allowed_headers']);
        $allowedMethods = implode(', ', $this->config['cors_allowed_methods']);
        $forcedHeaders = $this->config['cors_forced_headers'];

        // If we want to allow any domain to access the API
        if ($this->config['cors_allow_any_domain'] === true) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: ' . $allowedHeaders);
            header('Access-Control-Allow-Methods: ' . $allowedMethods);
        } else {
            // We're going to allow only certain domains access
            // Store the HTTP Origin header
            $origin = $this->ci->input->server('HTTP_ORIGIN');
            if (empty($origin) || $origin === 'null') {
                $origin = '';
            }

            // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
            if (in_array($origin, $this->config['cors_allowed_origins'])) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Headers: ' . $allowedHeaders);
                header('Access-Control-Allow-Methods: ' . $allowedMethods);
            }
        }

        // If there are headers that should be forced in the CORS check, add them now
        if (!empty($forcedHeaders)) {
            foreach ($forcedHeaders as $header => $value) {
                header($header . ': ' . $value);
            }
        }

        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        if ($this->getMethod() === 'options') {
            exit();
        }
    }
}
