<?php

namespace Moudarir\CodeigniterApi\Http;

use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use CI_Controller;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Moudarir\CodeigniterApi\Models\ApiKeyLimit;
use UnexpectedValueException;

class Authorization
{

    /**
     * @var CI_Controller
     */
    private CI_Controller $ci;

    /**
     * @var Request
     */
    private static Request $request;

    /**
     * @var array|null
     */
    private ?array $apiKey = null;

    /**
     * @var array
     */
    private array $config;

    /**
     * @var array|null
     */
    private ?array $auth_data = null;

    /**
     * @var bool
     */
    private bool $authorized = false;

    /**
     * Authorization constructor.
     *
     * @param array $config Rest Api Configuration
     * @param Request $request
     */
    public function __construct(array $config, Request $request)
    {
        $this->ci = &get_instance();
        $this->config = $config;

        if (!isset(self::$request)) {
            self::$request = $request;
        }
    }

    /**
     * @throws Exception
     */
    public function check()
    {
        $http_auth = $this->httpServerAuth();

        if ($http_auth === null) {
            throw new Exception($this->ci->lang->line('rest_auth_key_not_found'), Config::HTTP_UNAUTHORIZED);
        }

        if (strpos(strtolower($http_auth), 'basic') === 0) {
            $this->basic($http_auth);
        }

        if (strpos(strtolower($http_auth), 'bearer') === 0) {
            $this->bearer($http_auth);
        }
    }

    /**
     * Check if the requests to a controller method exceed a limit.
     *
     * @param string $controllerMethod The method being called
     * @return bool TRUE the call limit is below the threshold; otherwise, FALSE
     */
    public function checkLimits(string $controllerMethod): bool
    {
        $apiKey = $this->getApiKey();

        if ($apiKey['limits'] === null) {
            // Everything is fine
            return true;
        }

        $keyId = $apiKey['id'];

        switch ($this->config['limits_type']) {
            case 'IP_ADDRESS':
                $request = 'ip:' . $this->ci->input->ip_address();
                break;
            case 'API_KEY':
                $request = 'api-key:' . $keyId;
                break;
            case 'METHOD_NAME':
                $request = 'method:' . $controllerMethod;
                break;
            case 'ROUTED_URL':
            default:
                $uri = $this->ci->uri->ruri_string();
                if (strpos(strrev($uri), strrev(self::$request->getOutputFormat())) === 0) {
                    $uri = substr($uri, 0, -strlen(self::$request->getOutputFormat()) - 1);
                }

                // It's good to differentiate GET from PUT
                $request = 'uri:' . $uri . ':' . self::$request->getMethod();
                break;
        }

        // Get data about a keys' usage and limit to one row
        $entity = new ApiKeyLimit();
        $apiLimit = $entity->find(null, ['key_id' => $keyId, 'request' => $request]);

        // No calls have been made for this key
        if ($apiLimit === null) {
            // Create a new row for the following key
            $limitId = $entity->add([
                'key_id' => $keyId,
                'request' => $request,
                'counter' => 1,
                'started_at' => time()
            ]);
            return $limitId !== null;
        }

        // Been a time limit (or by default an hour) since they called
        // How many times can you get to this method in a defined limits_timeout (default: 1 hour)?
        $limitsTimeout = (int)$apiKey['reset_limits_after'] > 0
            ? ((int)$apiKey['reset_limits_after'] * $this->config['default_limits_timeout'])
            : $this->config['default_limits_timeout'];

        if ((int)$apiLimit['started_at'] < (time() - $limitsTimeout)) {
            // Reset the started period and counter
            return $entity->edit($apiLimit['id'], [
                'counter' => 1,
                'started_at' => time()
            ]);
        }

        // They have called within the hour, so lets update
        // The limit has been exceeded
        if ((int)$apiLimit['counter'] >= (int)$apiKey['limits']) {
            return false;
        }

        // Increase the counter by one
        return $entity->increaseCounter($apiLimit['id']);
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * @return array|null
     */
    public function getAuthData(): ?array
    {
        return $this->auth_data;
    }

    /**
     * @return array|null
     */
    public function getApiKey(): ?array
    {
        return $this->apiKey ?? null;
    }

    /**
     * @param array|null $apiKey
     * @return self
     */
    private function setApiKey(?array $apiKey = null): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @param string $http_auth
     * @throws Exception
     */
    private function basic(string $http_auth)
    {
        // Search online for HTTP_AUTHORIZATION workaround to explain what this is doing
        [$username, $password] = explode(':', base64_decode(substr($http_auth, 6)));
        $key = $this->apiKeyValue();

        if ($this->config['enable_api_key'] === true && empty($key)) {
            throw new Exception($this->ci->lang->line('rest_api_key_not_found'), Config::HTTP_UNAUTHORIZED);
        }

        if (empty($username) || empty($password)) {
            throw new Exception($this->ci->lang->line('rest_empty_username_or_password'), Config::HTTP_UNAUTHORIZED);
        }

        $basicAuthClass = $this->config['api_key_auth_basic_class'];

        if (!class_exists($basicAuthClass)) {
            throw new Exception("The Basic Authorization class not exists.", Config::HTTP_INTERNAL_ERROR);
        }

        $basicAuthMethod = $this->config['api_key_auth_basic_method'];
        if (!method_exists($basicAuthClass, $basicAuthMethod)) {
            throw new Exception("The Basic Authorization method not exists.", Config::HTTP_INTERNAL_ERROR);
        }

        $arguments = [$username, $password];

        if ($this->config['enable_api_key'] === true) {
            $arguments[] = $key;
        }

        $authClass = new $basicAuthClass();
        // Call the controller method and passed arguments
        $apiKey = call_user_func_array([$authClass, $basicAuthMethod], $arguments);

        if ($apiKey !== null) {
            $this->authorized = true;
            $this->setApiKey($apiKey);

            // If "is private key" is enabled, compare the ip address with the list
            // of valid ip addresses stored in the database
            if ($apiKey['ip_addresses'] !== null) {
                // multiple ip addresses must be separated using a comma
                $ipaList = explode(',', $apiKey['ip_addresses']);
                $ipAddress = $this->ci->input->ip_address();
                $ips = array_filter($ipaList, fn ($ipa) => (trim($ipa) === $ipAddress));

                // There is a match, set the the "authorized" value to FALSE
                if (!empty($ips)) {
                    $this->authorized = false;
                    throw new Exception($this->ci->lang->line('rest_api_ip_address_denied'), Config::HTTP_UNAUTHORIZED);
                }
            }
        }
    }

    /**
     * @param string $http_auth
     * @throws Exception
     */
    private function bearer(string $http_auth)
    {
        try {
            $token = substr($http_auth, 7);
            $secret = getenv("JWT_SECRET");
            $secret !== false || $secret = $this->config['jwt_secret'];
            $authData = (array) JWT::decode($token, new Key($secret, 'HS256'));
            $this->auth_data = (array) $authData;
            $this->authorized = true;
        } catch (InvalidArgumentException $e) {
            throw new Exception($this->ci->lang->line('rest_jwt_internal_server_error'), Config::HTTP_INTERNAL_ERROR);
        } catch (DomainException $e) {
            throw new Exception($this->ci->lang->line('rest_jwt_internal_server_error'), Config::HTTP_INTERNAL_ERROR);
        } catch (SignatureInvalidException $e) {
            throw new Exception($this->ci->lang->line('rest_jwt_signature_verification_failed'), Config::HTTP_UNAUTHORIZED);
        } catch (BeforeValidException $e) {
            throw new Exception($e->getMessage(), Config::HTTP_UNAUTHORIZED);
        } catch (ExpiredException $e) {
            throw new Exception($this->ci->lang->line('rest_jwt_expired_token'), Config::HTTP_UNAUTHORIZED);
        } catch (UnexpectedValueException $e) {
            throw new Exception($this->ci->lang->line('rest_jwt_auth_failed'), Config::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @return string|null
     */
    private function httpServerAuth(): ?string
    {
        return $this->ci->input->server('HTTP_AUTHORIZATION') ?: $this->ci->input->server('HTTP_AUTHENTICATION');
    }

    /**
     * @return string|null
     */
    private function apiKeyValue(): ?string
    {
        $apiKeyName = $this->config['api_key_name'];
        // Work out the name of the SERVER entry based on config
        $keyName = 'HTTP_' . strtoupper(str_replace('-', '_', $apiKeyName));
        // Find the key from server or arguments
        return array_key_exists($apiKeyName, self::$request->getArgs())
            ? self::$request->getArgs()[$apiKeyName]
            : $this->ci->input->server($keyName);
    }
}
