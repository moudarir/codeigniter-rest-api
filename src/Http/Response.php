<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Moudarir\CodeigniterApi\Exceptions\FormatException;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use function get_instance;

class Response
{

    private CI_Controller $ci;

    private ?Logger $logger;

    private string $output_format;

    private array $config;

    private array $args = [];

    public function __construct(array $config, ?Request $request = null, ?Logger $logger = null)
    {
        $this->ci =& get_instance();
        $this->logger = $logger;
        $this->config = $config;

        if ($request instanceof Request) {
            $this->setOutputFormat($request->getOutputFormat())->setArgs($request->getArgs());
        } else {
            $this->setOutputFormat($this->config['default_output_format']);
        }
    }

    public function ok(array $result)
    {
        $data = [$this->config['error_field_name'] => false];

        if (\array_key_exists('message', $result)) {
            $data[$this->config['message_field_name']] = $result['message'];
        }

        if (\array_key_exists('total', $result) || \array_key_exists('count', $result)) {
            $data[$this->config['total_field_name']] = $result['total'] ?? $result['count'];
        }

        if (\array_key_exists('page', $result)) {
            $data[$this->config['page_field_name']] = $result['page'];
        }
        if (\array_key_exists('limit', $result) || \array_key_exists('per_page', $result)) {
            $data[$this->config['limit_field_name']] = $result['limit'] ?? $result['per_page'];
        }

        if (\array_key_exists('data', $result) || \array_key_exists('items', $result) || \array_key_exists('item', $result)) {
            $data[$this->config['data_field_name']] = $result['data'] ?? ($result['items'] ?? $result['item']);
        }

        $this->response($data, Config::HTTP_OK);
    }

    /**
     * Error (with 200 response code)
     */
    public function error($error)
    {
        $data = [$this->config['error_field_name'] => true];

        if (\is_array($error)) {
            $data[$this->config['reasons_field_name']] = $error;
        } else {
            $data[$this->config['message_field_name']] = $error;
        }

        $this->response($data, Config::HTTP_OK);
    }

    /**
     * CREATED (201)
     */
    public function created(?string $message = null)
    {
        $data = [$this->config['error_field_name'] => false];

        if (!empty($message)) {
            $data[$this->config['message_field_name']] = $message;
        }

        $this->response($data, Config::HTTP_CREATED);
    }

    /**
     * NOT MODIFIED (304)
     */
    public function notModified(?string $message = null)
    {
        $data = [$this->config['error_field_name'] => false];

        if (!empty($message)) {
            $data[$this->config['message_field_name']] = $message;
        }

        $this->response($data, Config::HTTP_NOT_MODIFIED);
    }

    /**
     * BAD REQUEST (400)
     */
    public function badRequest()
    {
        $this->response(null, Config::HTTP_BAD_REQUEST);
    }

    /**
     * UNAUTHORIZED (401)
     */
    public function unauthorized(?string $message = null)
    {
        $this->response([
            $this->config['error_field_name'] => true,
            $this->config['message_field_name'] => $message ?: $this->ci->lang->line('rest_unauthorized')
        ], Config::HTTP_UNAUTHORIZED);
    }

    /**
     * FORBIDDEN (403)
     */
    public function forbidden(?string $message = null)
    {
        $this->response([
            $this->config['error_field_name'] => true,
            $this->config['message_field_name'] => $message ?: $this->ci->lang->line('rest_forbidden_access')
        ], Config::HTTP_FORBIDDEN);
    }

    /**
     * NOT FOUND (404)
     */
    public function notFound(?string $message = null)
    {
        $this->response([
            $this->config['error_field_name'] => true,
            $this->config['message_field_name'] => $message ?: $this->ci->lang->line('rest_not_found')
        ], Config::HTTP_NOT_FOUND);
    }

    /**
     * METHOD NOT ALLOWED (405)
     */
    public function methodNotAllowed(?string $message = null)
    {
        $this->response([
            $this->config['error_field_name'] => true,
            $this->config['message_field_name'] => $message ?: $this->ci->lang->line('rest_method_not_allowed')
        ], Config::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * INTERNAL ERROR (500)
     */
    public function internalError()
    {
        $this->response([
            $this->config['error_field_name'] => true,
            $this->config['message_field_name'] => $this->ci->lang->line('rest_internal_server_error'),
        ], Config::HTTP_INTERNAL_ERROR);
    }

    /**
     * Getters
     */

    public function getOutputFormat(): string
    {
        return $this->output_format;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function response($data = null, ?int $http_code = null, bool $continue = false)
    {
        if ($this->config['enable_profiling'] === true) {
            echo self::jsonify([$this->config['message_field_name'] => $data]);
            exit();
        }

        \ob_start();

        $output = null;
        $http_code = $http_code ?: Config::HTTP_NOT_FOUND;
        $outputFormat = $this->getOutputFormat();
        $method = CommonHelper::stringToCamelcase('to_' . $outputFormat);

        if (\method_exists(Format::class, $method)) {
            try {
                $output = Format::factory($data)->$method();
                $formats = Config::SUPPORTED_FORMATS;
                $args = $this->getArgs();

                if (isset($args['callback']) && $outputFormat === 'json' &&
                    \preg_match('/^'.$args['callback'].'/', $output)
                ) {
                    $this->ci->output->set_content_type($formats['jsonp']);
                } else {
                    $this->ci->output->set_content_type($formats[$outputFormat]);
                }

                if ($outputFormat === 'array') {
                    $output = Format::factory($output)->toJson();
                }
            } catch (FormatException $exception) {
                $this->response([
                    $this->config['error_field_name']   => true,
                    $this->config['message_field_name'] => $exception->getMessage()
                ], $exception->getCode());
            }
        } else {
            if (!\is_string($data)) {
                try {
                    $data = Format::factory($data)->toJson();
                } catch (FormatException $exception) {
                    $this->response([
                        $this->config['error_field_name']  => true,
                        $this->config['message_field_name'] => $exception->getMessage()
                    ], $exception->getCode());
                }
            }

            $output = $data;
        }

        $this->ci->output->set_status_header($http_code);

        if ($this->config['enable_logging'] === true && $this->logger !== null) {
            $this->logger->push(['response_code' => $http_code]);
        }

        $this->ci->output->set_output($output);

        if ($continue === false) {
            $this->ci->output->_display();
            \ob_end_flush();
            exit();
        } else {
            if (\is_callable('fastcgi_finish_request')) {
                $this->ci->output->_display();
                \ob_end_flush();
                \fastcgi_finish_request();
                \ignore_user_abort(true);
            } else {
                \ob_end_flush();
            }
        }
    }

    /**
     * @param string $output_format
     * @return self
     */
    private function setOutputFormat(string $output_format): self
    {
        $this->output_format = $output_format;
        return $this;
    }

    private function setArgs(array $args): void
    {
        $this->args = $args;
    }

    private static function jsonify(array $data): string
    {
        \header('Content-Type: application/json; charset=utf-8');
        return \json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}
