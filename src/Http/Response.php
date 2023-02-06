<?php

namespace Moudarir\CodeigniterApi\Http;

use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use CI_Controller;
use Exception;

class Response
{

    /**
     * @var CI_Controller
     */
    private CI_Controller $ci;

    /**
     * @var Logger|null
     */
    private ?Logger $logger;

    /**
     * @var array
     */
    private array $formats = Config::ALL_SUPPORTED_FORMATS;

    /**
     * @var string
     */
    private string $output_format = Config::DEFAULT_OUTPUT_FORMAT;

    /**
     * @var array
     */
    private array $args = [];

    /**
     * Response constructor.
     *
     * @param Request|null $request
     * @param Logger|null $logger
     */
    public function __construct(?Request $request = null, ?Logger $logger = null)
    {
        $this->ci = &get_instance();
        $this->logger = $logger;

        if ($request instanceof Request) {
            $this->setOutputFormat($request->getOutputFormat())
                ->setArgs($request->getArgs());
        }
    }

    /**
     * OK (200)
     *
     * @param array $result
     */
    public function ok(array $result)
    {
        $data = [
            Config::ERROR_FIELD_NAME   => false,
            Config::MESSAGE_FIELD_NAME => '',
        ];

        if (array_key_exists('message', $result)) {
            $data[Config::MESSAGE_FIELD_NAME] = $result['message'];
        }

        if (array_key_exists('total', $result)) {
            $data[Config::TOTAL_FIELD_NAME] = $result['total'];
        } elseif (array_key_exists('count', $result)) {
            $data[Config::TOTAL_FIELD_NAME] = $result['count'];
        }

        if (array_key_exists('page', $result)) {
            $data[Config::NUM_PAGE_FIELD_NAME] = $result['page'];
        }

        if (array_key_exists('data', $result)) {
            $data[Config::DATA_FIELD_NAME] = $result['data'];
        } elseif (array_key_exists('items', $result)) {
            $data[Config::DATA_FIELD_NAME] = $result['items'];
        } elseif (array_key_exists('item', $result)) {
            $data[Config::DATA_FIELD_NAME] = $result['item'];
        }

        $this->response($data, Config::HTTP_OK);
    }

    /**
     * Error (with 200 response code)
     *
     * @param mixed $error
     */
    public function error($error)
    {
        $this->response([
            Config::ERROR_FIELD_NAME   => true,
            Config::MESSAGE_FIELD_NAME => $error,
        ], Config::HTTP_OK);
    }

    /**
     * CREATED (201)
     *
     * @param string|null $message
     */
    public function created(?string $message = null)
    {
        $data = [
            Config::ERROR_FIELD_NAME => false,
        ];

        if (!empty($message)) {
            $data[Config::MESSAGE_FIELD_NAME] = $message;
        }

        $this->response($data, Config::HTTP_CREATED);
    }

    /**
     * NOT MODIFIED (304)
     *
     * @param string|null $message
     */
    public function modified(?string $message = null)
    {
        $data = [
            Config::ERROR_FIELD_NAME => false,
        ];

        if (!empty($message)) {
            $data[Config::MESSAGE_FIELD_NAME] = $message;
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
     * NOT FOUND (401)
     *
     * @param string|null $message
     */
    public function unauthorized(?string $message = null)
    {
        $this->response([
            Config::ERROR_FIELD_NAME   => true,
            Config::MESSAGE_FIELD_NAME => $message ?: "Non autorisée."
        ], Config::HTTP_UNAUTHORIZED);
    }

    /**
     * NOT FOUND (403)
     *
     * @param string|null $message
     */
    public function forbidden(?string $message = null)
    {
        $this->response([
            Config::ERROR_FIELD_NAME   => true,
            Config::MESSAGE_FIELD_NAME => $message ?: "Accès interdit."
        ], Config::HTTP_FORBIDDEN);
    }

    /**
     * NOT FOUND (404)
     *
     * @param string|null $message
     */
    public function notFound(?string $message = null)
    {
        $this->response([
            Config::ERROR_FIELD_NAME   => true,
            Config::MESSAGE_FIELD_NAME => $message ?: "Enregistrement introuvable."
        ], Config::HTTP_NOT_FOUND);
    }

    /**
     * NOT FOUND (405)
     *
     * @param string|null $message
     */
    public function methodNotAllowed(?string $message = null)
    {
        $this->response([
            Config::ERROR_FIELD_NAME   => true,
            Config::MESSAGE_FIELD_NAME => $message ?: "Méthode introuvable."
        ], Config::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Getters
     */

    /**
     * @return string
     */
    public function getOutputFormat(): string
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
     * Takes mixed data and optionally a status code, then creates the response.
     *
     * @param array|string|null $data Data to output to the user
     * @param int|null $http_code     HTTP status code
     * @param bool $continue          TRUE to flush the response to the client and continue
     *                                running the script; otherwise, exit
     */
    public function response($data = null, ?int $http_code = null, bool $continue = false)
    {
        //if profiling enabled then print profiling data
        if (Config::ENABLE_PROFILING === false) {
            ob_start();
            // If the HTTP status is not NULL, then cast as an integer
            if ($http_code !== null) {
                // So as to be safe later on in the process
                $http_code = (int)$http_code;
            }

            // Set the output as NULL by default
            $output = null;

            // If data is NULL and no HTTP status code provided, then display, error and exit
            if ($data === null && $http_code === null) {
                $http_code = Config::HTTP_NOT_FOUND;
            } elseif ($data !== null) { // If data is not NULL and a HTTP status code provided, then continue
                $outputFormat = $this->getOutputFormat();
                $args = $this->getArgs();
                $method = CommonHelper::stringToCamelcase('to_' . $outputFormat);

                // If the format method exists, call and return the output in that format
                if (method_exists(Format::class, $method)) {
                    // CORB protection
                    // First, get the output content.
                    try {
                        $output = Format::factory($data)->$method();

                        // Set the format header
                        // Then, check if the client asked for a callback, and if the output contains this callback :
                        if (isset($args['callback']) && $outputFormat === 'json' &&
                            preg_match('/^'.$args['callback'].'/', $output)
                        ) {
                            $this->ci->output->set_content_type($this->formats['jsonp']);
                        } else {
                            $this->ci->output->set_content_type($this->formats[$outputFormat]);
                        }

                        // An array must be parsed as a string, so as not to cause an array to string error
                        // Json is the most appropriate form for such a data type
                        if ($outputFormat === 'array') {
                            $output = Format::factory($output)->toJson();
                        }
                    } catch (Exception $e) {
                        $this->response([
                            Config::ERROR_FIELD_NAME   => true,
                            Config::MESSAGE_FIELD_NAME => $e->getMessage()
                        ], $e->getCode());
                    }
                } else {
                    // If an array or object, then parse as a json, so as to be a 'string'
                    if (is_array($data) || is_object($data)) {
                        try {
                            $data = Format::factory($data)->toJson();
                        } catch (Exception $e) {
                            $this->response([
                                Config::ERROR_FIELD_NAME   => true,
                                Config::MESSAGE_FIELD_NAME => $e->getMessage()
                            ], $e->getCode());
                        }
                    }

                    // Format is not supported, so output the raw data as a string
                    $output = $data;
                }
            }

            // If not greater than zero, then set the HTTP status code as 200 by default
            // Though perhaps 500 should be set instead, for the developer not passing a
            // correct HTTP status code
            $http_code > 0 || $http_code = Config::HTTP_OK;

            $this->ci->output->set_status_header($http_code);

            // JC: Log response code only if rest logging enabled
            if (Config::ENABLE_LOGGING === true && $this->logger !== null) {
                $this->logger->update(['response_code' => $http_code]);
            }

            // Output the data
            $this->ci->output->set_output($output);

            if ($continue === false) {
                // Display the data and exit execution
                $this->ci->output->_display();
                exit();
            } else {
                if (is_callable('fastcgi_finish_request')) {
                    // Terminates connection and returns response to client on PHP-FPM.
                    $this->ci->output->_display();
                    ob_end_flush();
                    fastcgi_finish_request();
                    ignore_user_abort(true);
                } else {
                    // Legacy compatibility.
                    ob_end_flush();
                }
            }
            ob_end_flush();
        } else { // Otherwise dump the output automatically
            $this->response($data, $http_code);
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

    /**
     * @param array $args
     * @return self
     */
    private function setArgs(array $args): self
    {
        $this->args = $args;
        return $this;
    }
}
