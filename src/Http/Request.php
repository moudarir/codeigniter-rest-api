<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Moudarir\CodeigniterApi\Exceptions\FormatException;
use Moudarir\CodeigniterApi\Exceptions\RequestException;
use Moudarir\CodeigniterApi\Helpers\StringHelper;
use function get_instance;

class Request
{

    private CI_Controller $ci;

    private array $config;

    private ?string $method;

    private ?string $input_format = null;

    private string $output_format;

    /**
     * @var array|string|bool|null
     */
    private $body = null;

    /**
     * The arguments from GET, POST, PUT, DELETE, PATCH, HEAD and OPTIONS request methods combined.
     */
    private array $args = [];

    /**
     * The arguments for the query parameters.
     */
    private ?array $query_args = [];

    /**
     * The arguments for the GET request method.
     */
    private array $get_args = [];

    /**
     * The arguments for the POST request method.
     */
    private array $post_args = [];

    /**
     * The arguments for the PUT request method.
     */
    private array $put_args = [];

    /**
     * The arguments for the DELETE request method.
     */
    private array $delete_args = [];

    /**
     * The arguments for the PATCH request method.
     */
    private array $patch_args = [];

    /**
     * The arguments for the HEAD request method.
     */
    private array $head_args = [];

    /**
     * The arguments for the OPTIONS request method.
     */
    private array $options_args = [];

    /**
     * @throws RequestException|FormatException
     */
    public function __construct(array $config)
    {
        $this->ci =& get_instance();
        $this->config = $config;

        if ($this->config['enable_ip_blacklist'] === true) {
            $this->blacklistedIpCheck();
        }

        if ($this->config['enable_ip_whitelist'] === true) {
            $this->whitelistIpCheck();
        }

        $this->setMethod();

        if ($this->config['enable_cors_check'] === true) {
            $this->checkCORS();
        }

        $this->setQueryArgs();
        $this->setGet($this->ci->uri->ruri_to_assoc());
        $this->setInputFormat();
        $setMethod = 'set' . $this->getMethod(true);
        $this->$setMethod();
        $this->setOutputFormat();
        $methodArgs = $this->getMethod() . '_args';

        if ($this->getInputFormat() !== null && $this->body) {
            $this->$methodArgs = Format::factory($this->body, $this->getInputFormat())->toArray();
        }

        $this->setHead(true);
        $this->setArgs($this->$methodArgs);

        if ($this->ci->input->is_ajax_request() === false && $this->config['allow_ajax_only'] === true) {
            throw new RequestException($this->ci->lang->line('rest_allow_ajax_only'), Config::HTTP_NOT_ACCEPTABLE);
        }
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getMethod(bool $ucFirst = false): ?string
    {
        if ($this->method === null || $ucFirst === false) {
            return $this->method;
        }

        return \ucfirst($this->method);
    }

    public function getInputFormat(): ?string
    {
        return $this->input_format;
    }

    public function getOutputFormat(): string
    {
        return $this->output_format;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getGetArgs(): array
    {
        return $this->get_args;
    }

    public function getPostArgs(): array
    {
        return $this->post_args;
    }

    public function getPutArgs(): array
    {
        return $this->put_args;
    }

    public function getPatchArgs(): array
    {
        return $this->patch_args;
    }

    public function getDeleteArgs(): array
    {
        return $this->delete_args;
    }

    public function getHeadArgs(): array
    {
        return $this->head_args;
    }

    public function getOptionsArgs(): array
    {
        return $this->options_args;
    }

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
        $method = null;

        if ($this->config['enable_emulate_request'] === true) {
            $method = $this->ci->input->post('_method');
            if ($method === null) {
                $method = $this->ci->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }
        }

        if (empty($method)) {
            $method = $this->ci->input->method();
        }

        $method = \strtolower($method);

        if (\in_array($method, $this->config['allowed_http_methods']) && \method_exists($this, 'set' . \ucfirst($method))) {
            $this->method = $method;
        } else {
            $this->method = 'get';
        }
    }

    private function setInputFormat()
    {
        $contentType = $this->ci->input->server('CONTENT_TYPE');

        if (!empty($contentType)) {
            $contentType = (StringHelper::strContains($contentType, ';') ? \current(\explode(';', $contentType)) : $contentType);

            foreach (Config::SUPPORTED_FORMATS as $type => $mime) {
                if ($contentType === $mime) {
                    $this->input_format = $type;
                    break;
                }
            }
        }
    }

    private function setOutputFormat()
    {
        $formats = Config::SUPPORTED_FORMATS;
        $output_format = null;
        $http_accept = $this->ci->input->server('HTTP_ACCEPT');

        if ($http_accept !== null && $http_accept !== '*/*') {
            foreach ($formats as $format => $mime) {
                if ($http_accept === $mime) {
                    $output_format = $format;
                    break;
                }

                if (StringHelper::strContains($http_accept, $format)) {
                    if ($format !== 'html' && $format !== 'xml') {
                        // If not HTML or XML assume it's correct
                        $output_format = $format;
                        break;
                    } elseif ($format === 'html' && !StringHelper::strContains($http_accept, 'xml')) {
                        // HTML or XML have shown up as a match
                        // If it is truly HTML, it won't want any XML
                        $output_format = $format;
                        break;
                    } elseif ($format === 'xml' && !StringHelper::strContains($http_accept, 'html')) {
                        // If it is truly XML, it won't want any HTML
                        $output_format = $format;
                        break;
                    }
                }
            }
        }

        $this->output_format = $output_format ?: $this->config['default_output_format'];
    }

    /**
     * Merge both for one mega-args variable
     */
    private function setArgs(array $extra = [])
    {
        $this->args = \array_merge(
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
     */
    private function setGet(array $merge_with = [])
    {
        $this->get_args = !empty($merge_with)
            ? \array_merge($this->get_args, $merge_with)
            : \array_merge($this->get_args, $this->getQueryArgs());
    }

    /**
     * Parse the POST request arguments.
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
     */
    private function setPut()
    {
        if ($this->getInputFormat() !== null) {
            $this->body = $this->ci->input->raw_input_stream;
            if ($this->getInputFormat() === 'json') {
                $this->put_args = \json_decode($this->body, true);
            }
        } elseif ($this->getMethod() === 'put') {
            $this->put_args = $this->ci->input->input_stream() ?: [];
        }
    }

    /**
     * Parse the HEAD request arguments.
     */
    private function setHead(bool $manual = false)
    {
        if ($manual === true) {
            $this->head_args = \array_merge($this->head_args, $this->ci->input->request_headers());
        } else {
            \parse_str(\parse_url($this->ci->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);
            $this->head_args = \array_merge($this->head_args, $head);
        }
    }

    /**
     * Parse the OPTIONS request arguments.
     */
    private function setOptions()
    {
        \parse_str(\parse_url($this->ci->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);
        $this->options_args = \array_merge($this->options_args, $options);
    }

    /**
     * Parse the PATCH request arguments.
     */
    private function setPatch()
    {
        if ($this->getInputFormat() !== null) {
            $this->body = $this->ci->input->raw_input_stream;
        } elseif ($this->getMethod() === 'patch') {
            $this->patch_args = $this->ci->input->input_stream() ?: [];
        }
    }

    /**
     * Parse the DELETE request arguments.
     */
    private function setDelete()
    {
        if ($this->getMethod() === 'delete') {
            $this->delete_args = $this->ci->input->input_stream() ?: [];
        }
    }

    /**
     * Parse the query parameters
     */
    private function setQueryArgs(): void
    {
        $this->query_args = $this->ci->input->get();
    }

    /**
     * Checks if the client's ip is in the 'ip_blacklist' config and generates a 401 response
     *
     * @throws RequestException
     */
    private function blacklistedIpCheck()
    {
        $pattern = \sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->ci->input->ip_address());
        $blacklist = \implode(',', $this->config['ip_blacklist']);

        if (\preg_match($pattern, $blacklist)) {
            throw new RequestException($this->ci->lang->line('rest_ip_blacklist'));
        }
    }

    /**
     * Check if the client's ip is in the 'ip_whitelist' config and generates a 401 response.
     *
     * @throws RequestException
     */
    private function whitelistIpCheck()
    {
        $whitelist = $this->config['ip_whitelist'];

        if (\in_array($this->ci->input->ip_address(), $whitelist) === false) {
            throw new RequestException($this->ci->lang->line('rest_ip_whitelist'));
        }
    }

    /**
     * Checks allowed domains, and adds appropriate headers for HTTP access control (CORS)
     */
    private function checkCORS(): void
    {
        $allowedHeaders = \implode(', ', $this->config['cors_allowed_headers']);
        $allowedMethods = \implode(', ', $this->config['cors_allowed_methods']);
        $forcedHeaders = $this->config['cors_forced_headers'];

        // If we want to allow any domain to access the API
        if ($this->config['cors_allow_any_domain'] === true) {
            \header('Access-Control-Allow-Origin: *');
            \header('Access-Control-Allow-Headers: ' . $allowedHeaders);
            \header('Access-Control-Allow-Methods: ' . $allowedMethods);
        } else {
            // We're going to allow only certain domains access
            // Store the HTTP Origin header
            $origin = $this->ci->input->server('HTTP_ORIGIN');
            if (empty($origin) || $origin === 'null') {
                $origin = '';
            }

            // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
            if (\in_array($origin, $this->config['cors_allowed_origins'])) {
                \header('Access-Control-Allow-Origin: ' . $origin);
                \header('Access-Control-Allow-Headers: ' . $allowedHeaders);
                \header('Access-Control-Allow-Methods: ' . $allowedMethods);
            }
        }

        // If there are headers that should be forced in the CORS check, add them now
        if (!empty($forcedHeaders)) {
            foreach ($forcedHeaders as $header => $value) {
                \header($header . ': ' . $value);
            }
        }

        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        if ($this->getMethod() === 'options') {
            exit();
        }
    }
}
