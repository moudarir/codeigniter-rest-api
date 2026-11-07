<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Enums\HttpMethod;
use Moudarir\CIRestApi\Exceptions\ApiException;
use ErrorException;
use JsonException;
use Moudarir\CIRestApi\Helpers\Common;
use Moudarir\Helpers\FileHelper;

final class ApiRequest
{

    public HttpMethod $httpMethod;

    public ?string $input_format = null {
        get {
            return $this->input_format;
        }
    }

    private string $stream_input;

    private array $decoded_stream_input;

    private array $query_args;

    private array $get_args;

    private array $post_args;

    private array $put_args;

    private array $delete_args;

    private array $patch_args;

    private array $head_args;

    private array $options_args;

    private array $headers = [];

    /**
     * @throws ApiException
     */
    public function __construct(private readonly ApiConfig $apiConfig)
    {
        $this->blacklistedIpCheck();
        $this->whitelistIpCheck();
        $this->setMethod();
        $this->corsCheck();

        $this->setQueryArgs();
        $this->setInputFormat();
        $this->setArgs();
    }

    public function get(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->get_args, $xssClean);
        }

        return array_key_exists($key, $this->get_args)
            ? self::xssClean($this->get_args[$key], $xssClean)
            : null;
    }

    public function post(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->post_args, $xssClean);
        }

        return array_key_exists($key, $this->post_args)
            ? self::xssClean($this->post_args[$key], $xssClean)
            : null;
    }

    public function put(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->put_args, $xssClean);
        }

        return array_key_exists($key, $this->put_args)
            ? self::xssClean($this->put_args[$key], $xssClean)
            : null;
    }

    public function patch(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->patch_args, $xssClean);
        }

        return array_key_exists($key, $this->patch_args)
            ? self::xssClean($this->patch_args[$key], $xssClean)
            : null;
    }

    public function delete(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->delete_args, $xssClean);
        }

        return array_key_exists($key, $this->delete_args)
            ? self::xssClean($this->delete_args[$key], $xssClean)
            : null;
    }

    public function head(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->head_args, $xssClean);
        }

        return array_key_exists($key, $this->head_args)
            ? self::xssClean($this->head_args[$key], $xssClean)
            : null;
    }

    public function options(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->options_args, $xssClean);
        }

        return array_key_exists($key, $this->options_args)
            ? self::xssClean($this->options_args[$key], $xssClean)
            : null;
    }

    public function query(int|string|null $key = null, bool $xssClean = true): array|bool|float|int|string|null
    {
        if ($key === null) {
            return self::xssClean($this->query_args, $xssClean);
        }

        return array_key_exists($key, $this->query_args)
            ? self::xssClean($this->query_args[$key], $xssClean)
            : null;
    }

    public function server(int|string|null $key, bool $xssClean = false): mixed
    {
        return Common::fetchFromArray($_SERVER, $key, $xssClean);
    }

    public function headers(bool $xssClean = false)
    {
        // If header is already defined, return it immediately
        if ($this->headers !== []) {
            return Common::fetchFromArray($this->headers, null, $xssClean);
        }

        // In Apache, you can simply call apache_request_headers()
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $this->headers = $headers !== false ? $headers : [];
        } else {
            isset($_SERVER['CONTENT_TYPE']) && $this->headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];

            foreach ($_SERVER as $key => $val) {
                if (sscanf($key, 'HTTP_%s', $header) === 1) {
                    // take SOME_HEADER and turn it into Some-Header
                    $header = str_replace('_', ' ', strtolower($header));
                    $header = str_replace(' ', '-', ucwords($header));

                    $this->headers[$header] = $val;
                }
            }
        }

        return Common::fetchFromArray($this->headers, null, $xssClean);
    }

    /**
     * @throws ApiException
     */
    private function setMethod(): void
    {
        $method = strtoupper($this->server('REQUEST_METHOD'));

        if (($httpMethod = HttpMethod::tryFrom($method)) === null) {
            throw ApiException::httpMethodNotRecognized($method);
        }

        if (
            !in_array($httpMethod, $this->apiConfig->allowedHttpMethods) ||
            !method_exists($this, 'set'.ucfirst($httpMethod->value))
        ) {
            throw ApiException::httpMethodNotAllowed($httpMethod->value);
        }

        $this->httpMethod = $httpMethod;
    }

    private function setInputFormat(): void
    {
        $contentType = $this->server('CONTENT_TYPE');

        if (!empty($contentType)) {
            $contentType = (str_contains($contentType, ';') ? current(explode(';', $contentType)) : $contentType);

            if ($contentType === 'application/json') {
                $this->input_format = 'json';
            }
        }
    }

    private function setGet(): void
    {
        $this->get_args ??= $this->query_args;
    }

    /**
     * @throws ApiException
     */
    private function setPost(): void
    {
        $this->post_args ??= (
            $this->input_format !== null
                ? self::formatArgs($this->streamInput())
                : $_POST
        );
    }

    /**
     * @throws ApiException
     */
    private function setPut(): void
    {
        $this->put_args ??= (
            $this->input_format !== null
                ? self::formatArgs($this->streamInput())
                : $this->decodedStreamInput()
        );
    }

    /**
     * @throws ApiException
     */
    private function setPatch(): void
    {
        $this->patch_args ??= (
            $this->input_format !== null
                ? self::formatArgs($this->streamInput())
                : $this->decodedStreamInput()
        );
    }

    /**
     * @throws ApiException
     */
    private function setDelete(): void
    {
        $this->delete_args ??= (
            $this->input_format !== null
                ? self::formatArgs($this->streamInput())
                : $this->decodedStreamInput()
        );
    }

    private function setHead(): void
    {
        $query = parse_url($this->server('REQUEST_URI'), PHP_URL_QUERY);

        if ($query !== null) {
            parse_str($query, $result);
            $this->head_args = is_array($result) ? $result : [];
        }
    }

    private function setOptions(): void
    {
        $query = parse_url($this->server('REQUEST_URI'), PHP_URL_QUERY);

        if ($query !== null) {
            parse_str($query, $result);
            $this->options_args = is_array($result) ? $result : [];
        }
    }

    private function setQueryArgs(): void
    {
        $this->query_args ??= Common::fetchFromArray($_GET, xssClean: true) ?: [];
    }

    /**
     * @throws ApiException
     */
    private function setArgs(): void
    {
        match ($this->httpMethod) {
            HttpMethod::GET => $this->setGet(),
            HttpMethod::POST => $this->setPost(),
            HttpMethod::PUT => $this->setPut(),
            HttpMethod::PATCH => $this->setPatch(),
            HttpMethod::DELETE => $this->setDelete(),
            HttpMethod::HEAD => $this->setHead(),
            HttpMethod::OPTIONS => $this->setOptions(),
        };
    }

    /**
     * @throws ApiException
     */
    private function streamInput(): string
    {
        try {
            return $this->stream_input ??= FileHelper::getContent('php://input', true);
        } catch (ErrorException $exception) {
            throw ApiException::generic("Erreur interne du serveur.", $exception);
        }
    }

    /**
     * @throws ApiException
     */
    private function decodedStreamInput(): array
    {
        if (isset($this->decoded_stream_input)) {
            return $this->decoded_stream_input;
        }

        parse_str($this->streamInput(), $result);
        $result = is_array($result) ? $result : [];

        return $this->decoded_stream_input = Common::fetchFromArray($result, xssClean: true);
    }

    /**
     * @throws ApiException
     */
    private static function formatArgs(mixed $data): array
    {
        if (is_string($data)) {
            try {
                $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw ApiException::bodyBadFormatted($exception);
            }
        } elseif (!is_array($data)) {
            $data = (array)$data;
        }

        $array = [];
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $array[$key] = self::formatArgs($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @throws ApiException
     */
    private function blacklistedIpCheck(): void
    {
        if ($this->apiConfig->enableIpBlacklist === true) {
            $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', Common::ipAddress());
            $blacklist = implode(',', $this->apiConfig->ipBlacklist);

            if (preg_match($pattern, $blacklist)) {
                throw ApiException::blacklistedIpAddress();
            }
        }
    }

    /**
     * @throws ApiException
     */
    private function whitelistIpCheck(): void
    {
        if ($this->apiConfig->enableIpWhitelist === true) {
            $ipAddress = Common::ipAddress();

            if (in_array($ipAddress, $this->apiConfig->ipWhitelist) === false) {
                throw ApiException::ipAddressNotAllowed($ipAddress);
            }
        }
    }

    private function corsCheck(): void
    {
        if ($this->apiConfig->enableCorsCheck === true) {
            $origin = $this->server('HTTP_ORIGIN');
            if (empty($origin) || $origin === 'null') {
                $origin = '';
            }

            if (in_array($origin, $this->apiConfig->corsAllowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Headers: ' . implode(', ', $this->apiConfig->corsHeaders));
                header('Access-Control-Allow-Methods: ' . implode(
                    ', ',
                    array_column($this->apiConfig->corsAllowedHttpMethods, 'value')
                ));
            }

            if ($this->apiConfig->corsForcedHeaders !== []) {
                foreach ($this->apiConfig->corsForcedHeaders as $header => $value) {
                    header($header . ': ' . $value);
                }
            }

            if ($this->httpMethod === HttpMethod::OPTIONS) {
                header('Access-Control-Max-Age: 86400');
                exit();
            }
        }
    }

    private static function xssClean(mixed $value, bool $xssClean = true): array|bool|float|int|string|null
    {
        return $xssClean === true ? Common::xssClean($value) : $value;
    }
}
