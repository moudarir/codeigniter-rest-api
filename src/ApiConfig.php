<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Enums\AuthMethod;
use Moudarir\CIRestApi\Enums\LimitType;
use Moudarir\CIRestApi\Enums\HttpMethod;
use Moudarir\CIRestApi\Exceptions\ApiException;
use Moudarir\CIRestApi\Interfaces\ApiClientInterface;
use Moudarir\CIRestApi\Interfaces\ApiLimitationInterface;
use Moudarir\CIRestApi\Interfaces\ApiLoggerInterface;
use Moudarir\Helpers\IsHelper;

final readonly class ApiConfig
{

    private const array ALLOWED_HTTP_METHODS = [
        HttpMethod::GET,
        HttpMethod::POST,
        HttpMethod::PUT,
        HttpMethod::DELETE,
        HttpMethod::OPTIONS
    ];

    private const array CORS_ALLOWED_HTTP_METHODS = [
        HttpMethod::GET,
        HttpMethod::POST,
        HttpMethod::PUT,
        HttpMethod::DELETE,
        HttpMethod::OPTIONS
    ];

    private const array CORS_ALLOWED_HEADERS = [
        'Origin',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Access-Control-Request-Method',
        'Authorization',
        'X-Api-key'
    ];

    private const array CORS_FORCED_HEADERS = ['Access-Control-Allow-Credentials' => 'true'];

    public function __construct(
        public bool       $enableLogging = false,
        public bool       $logOnlyAuthorized = true,
        public ?string    $loggerClassName = null,
        public bool       $enableAuthentication = false,
        public AuthMethod $authMethod = AuthMethod::BASIC,
        public ?string    $authenticationClassName = null,
        public ?string    $authenticationJWTSecret = null,
        public ?string    $authenticationJWTAlgo = null,
        public bool       $enableApiKey = false,
        public string     $apiKeyName = 'X-API-KEY',
        public bool       $enableLimits = false,
        public ?string    $limitsClassName = null,
        public LimitType  $limitType = LimitType::ROUTED_URL,
        public int        $limitsTimeout = 60,
        public int        $limitsNumRequests = 1000,
        public bool       $enableIpWhitelist = false,
        public bool       $enableIpBlacklist = false,
        public array      $ipWhitelist = ['127.0.0.1', '::1'],
        public array      $ipBlacklist = [],
        public array      $allowedHttpMethods = self::ALLOWED_HTTP_METHODS,
        public bool       $enableCorsCheck = true,
        public array      $corsAllowedHttpMethods = self::CORS_ALLOWED_HTTP_METHODS,
        public array      $corsHeaders = self::CORS_ALLOWED_HEADERS,
        public array      $corsForcedHeaders = self::CORS_FORCED_HEADERS,
        public array      $corsAllowedOrigins = [],
    )
    {}

    /**
     * @param bool $enableLogging
     * @param bool $logOnlyAuthorized
     * @param string|null $loggerClassName
     * @param bool $enableAuthentication
     * @param AuthMethod $authMethod
     * @param string|null $authenticationClassName
     * @param string|null $authenticationJWTSecret
     * @param string|null $authenticationJWTAlgo
     * @param bool $enableApiKey
     * @param string $apiKeyName
     * @param bool $enableLimits
     * @param string|null $limitsClassName
     * @param LimitType $limitType
     * @param int $limitsTimeout // in minutes
     * @param int $limitsNumRequests
     * @param bool $enableIpWhitelist
     * @param bool $enableIpBlacklist
     * @param array<string> $ipWhitelist
     * @param array<string> $ipBlacklist
     * @param array<HttpMethod> $allowedHttpMethods
     * @param bool $enableCorsCheck
     * @param array<HttpMethod> $corsAllowedHttpMethods
     * @param array<string> $corsHeaders
     * @param array<string, string> $corsForcedHeaders
     * @param array<string> $corsAllowedOrigins
     * @return self
     * @throws ApiException
     */
    public static function create(
        bool       $enableLogging = false,
        bool       $logOnlyAuthorized = true,
        ?string    $loggerClassName = null,
        bool       $enableAuthentication = false,
        AuthMethod $authMethod = AuthMethod::BASIC,
        ?string    $authenticationClassName = null,
        ?string    $authenticationJWTSecret = null,
        ?string    $authenticationJWTAlgo = null,
        bool       $enableApiKey = false,
        string     $apiKeyName = "X-API-KEY",
        bool       $enableLimits = false,
        ?string    $limitsClassName = null,
        LimitType  $limitType = LimitType::ROUTED_URL,
        int        $limitsTimeout = 60,
        int        $limitsNumRequests = 1000,
        bool       $enableIpWhitelist = false,
        bool       $enableIpBlacklist = false,
        array      $ipWhitelist = ["127.0.0.1", "::1"],
        array      $ipBlacklist = [],
        array      $allowedHttpMethods = self::ALLOWED_HTTP_METHODS,
        bool       $enableCorsCheck = true,
        array      $corsAllowedHttpMethods = self::CORS_ALLOWED_HTTP_METHODS,
        array      $corsHeaders = self::CORS_ALLOWED_HEADERS,
        array      $corsForcedHeaders = self::CORS_FORCED_HEADERS,
        array      $corsAllowedOrigins = [],
    ): self
    {
        if (
            !IsHelper::containsOnlyEnumCases(HttpMethod::class, $allowedHttpMethods, false) ||
            !IsHelper::containsOnlyEnumCases(HttpMethod::class, $corsAllowedHttpMethods, false)
        ) {
            throw ApiException::invalidHttpMethods();
        }

        if ($enableAuthentication === true) {
            if ($authenticationClassName === null || class_exists($authenticationClassName) === false) {
                throw ApiException::authenticationClassNameNotExists();
            }

            $interfaces = class_implements($authenticationClassName);
            if (in_array(ApiClientInterface::class, $interfaces) === false) {
                throw ApiException::authenticationClassNameNotImplement($authenticationClassName);
            }

            if (
                $authMethod === AuthMethod::BEARER &&
                (empty($authenticationJWTSecret) === null || empty($authenticationJWTAlgo) === null)
            ) {
                throw ApiException::authenticationFailed();
            }
        }

        if ($enableLogging === true) {
            if ($loggerClassName === null || class_exists($loggerClassName) === false) {
                throw ApiException::loggerClassNameNotExists();
            }

            $interfaces = class_implements($loggerClassName);
            if (in_array(ApiLoggerInterface::class, $interfaces) === false) {
                throw ApiException::loggerClassNameNotImplement($loggerClassName);
            }
        }

        if ($enableLimits === true) {
            if ($limitsClassName === null || class_exists($limitsClassName) === false) {
                throw ApiException::limitationClassNameNotExists();
            }

            $interfaces = class_implements($limitsClassName);
            if (in_array(ApiLimitationInterface::class, $interfaces) === false) {
                throw ApiException::limitationClassNameNotImplement($limitsClassName);
            }
        }

        return new self(
            $enableLogging,
            $logOnlyAuthorized,
            $loggerClassName,
            $enableAuthentication,
            $authMethod,
            $authenticationClassName,
            $authenticationJWTSecret,
            $authenticationJWTAlgo,
            $enableApiKey,
            $apiKeyName,
            $enableLimits,
            $limitsClassName,
            $limitType,
            $limitsTimeout,
            $limitsNumRequests,
            $enableIpWhitelist,
            $enableIpBlacklist,
            $ipWhitelist,
            $ipBlacklist,
            $allowedHttpMethods,
            $enableCorsCheck,
            $corsAllowedHttpMethods,
            $corsHeaders,
            $corsForcedHeaders,
            $corsAllowedOrigins,
        );
    }
}
