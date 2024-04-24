<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Moudarir\CodeigniterApi\Exceptions\AuthenticationException;
use Moudarir\CodeigniterApi\Exceptions\DatabaseCreateException;
use Moudarir\CodeigniterApi\Helpers\Carbonize;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use Moudarir\CodeigniterApi\Helpers\StringHelper;
use Moudarir\CodeigniterApi\Models\ApiKeyLimit;
use function get_instance;

class Authorization
{

    private CI_Controller $ci;

    private array $config;

    private Request $request;

    private ?array $api_key = null;

    private ?array $auth_data = null;

    private bool $authorized = false;

    private string $auth_method = 'auto';

    public function __construct(array $config, Request $request)
    {
        $this->ci =& get_instance();
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @throws AuthenticationException
     */
    public function check(string $auth_method)
    {
        $http_auth = $this->httpServerAuth();

        if ($http_auth === null) {
            throw new AuthenticationException($this->ci->lang->line('rest_auth_key_not_found'));
        }

        if ($auth_method === 'basic') {
            $this->basic($http_auth);
        } elseif ($auth_method === 'bearer') {
            $this->bearer($http_auth);
        } else {
            $this->auto($http_auth);
        }
    }

    public function checkLimits(string $controllerMethod): bool
    {
        if ($this->auth_method !== 'basic') {
            return true;
        }

        if ($this->api_key['limits'] === null) {
            return true;
        }

        $keyId = $this->api_key['id'];

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
                if (StringHelper::strStartsWith(\strrev($uri), \strrev($this->request->getOutputFormat()))) {
                    $uri = \substr($uri, 0, -\strlen($this->request->getOutputFormat()) - 1);
                }

                // It's good to differentiate GET from PUT
                $request = 'uri:' . $uri . ':' . $this->request->getMethod();
                break;
        }

        $entity = new ApiKeyLimit();
        $apiLimit = $entity->find(null, ['key_id' => $keyId, 'request' => $request]);

        if ($apiLimit === null) {
            try {
                $entity->setKeyId($keyId)->setRequest($request)->setCounter()->setStartedAt()->create();
                return true;
            } catch (DatabaseCreateException $exception) {
                CommonHelper::logException($exception);
                return false;
            }
        }

        // The limit has been exceeded
        if ($apiLimit->getCounter() >= (int)$this->api_key['limits']) {
            return false;
        }

        $timestamp = (new Carbonize())->getTimestamp();
        $defaultTimeout = $this->config['default_limits_timeout'];
        $limitsTimeout = (int)$this->api_key['reset_limits_after'] > 0
            ? ((int)$this->api_key['reset_limits_after'] * $defaultTimeout)
            : $defaultTimeout;

        if ($apiLimit->getStartedAt() < ($timestamp - $limitsTimeout)) {
            return $apiLimit->reset($timestamp);
        }

        return $apiLimit->increaseCounter();
    }

    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    public function getAuthData(): ?array
    {
        return $this->auth_data;
    }

    public function getApiKey(): ?array
    {
        return $this->api_key;
    }

    /**
     * @throws AuthenticationException
     */
    public function auto(string $http_auth)
    {
        if (StringHelper::strStartsWith(\strtolower($http_auth), 'basic')) {
            $this->basic($http_auth);
        }

        if (StringHelper::strStartsWith(\strtolower($http_auth), 'bearer')) {
            $this->bearer($http_auth);
        }
    }

    /**
     * @throws AuthenticationException
     */
    private function basic(string $http_auth)
    {
        $this->auth_method = 'basic';
        [$username, $password] = \explode(':', \base64_decode(\substr($http_auth, 6)));
        $key = $this->apiKeyValue();

        if ($this->config['enable_api_key'] === true && empty($key)) {
            throw new AuthenticationException($this->ci->lang->line('rest_api_key_not_found'));
        }

        if (empty($username) || empty($password)) {
            throw new AuthenticationException($this->ci->lang->line('rest_empty_username_or_password'));
        }

        $basicAuthClass = $this->config['api_key_auth_basic_class'];

        if (!\class_exists($basicAuthClass)) {
            throw new AuthenticationException("The Basic Authorization class not exists.", Config::HTTP_INTERNAL_ERROR);
        }

        $basicAuthMethod = $this->config['api_key_auth_basic_method'];
        if (!\method_exists($basicAuthClass, $basicAuthMethod)) {
            throw new AuthenticationException("The Basic Authorization method not exists.", Config::HTTP_INTERNAL_ERROR);
        }

        $arguments = [$username, $password];

        if ($this->config['enable_api_key'] === true) {
            $arguments[] = $key;
        }

        $authClass = new $basicAuthClass;
        $this->api_key = \call_user_func_array([$authClass, $basicAuthMethod], $arguments);

        if ($this->api_key === null) {
            throw new AuthenticationException($this->ci->lang->line('rest_auth_failed'));
        }

        $this->authorized = true;

        // If "is private key" is enabled, compare the ip address with the list
        // of valid ip addresses stored in the database
        if ($this->api_key['ip_addresses'] !== null) {
            $ipAddress = $this->ci->input->ip_address();

            // There is a match, set the "authorized" value to FALSE
            if (!\in_array($ipAddress, \explode(',', $this->api_key['ip_addresses']))) {
                $this->authorized = false;
                throw new AuthenticationException($this->ci->lang->line('rest_api_ip_address_denied'));
            }
        }
    }

    /**
     * @throws AuthenticationException
     */
    private function bearer(string $http_auth)
    {
        $this->auth_method = 'bearer';

        try {
            $token = \substr($http_auth, 7);
            $secret = \getenv("JWT_SECRET");
            $secret !== false || $secret = $this->config['jwt_secret'];
            $this->auth_data = (array) JWT::decode($token, new Key($secret, $this->config['jwt_algorithm']));
            $this->authorized = true;
        } catch (\InvalidArgumentException|\DomainException $exception) {
            throw new AuthenticationException($this->ci->lang->line('rest_internal_server_error'), Config::HTTP_INTERNAL_ERROR);
        } catch (SignatureInvalidException $exception) {
            throw new AuthenticationException($this->ci->lang->line('rest_jwt_signature_verification_failed'));
        } catch (BeforeValidException $exception) {
            throw new AuthenticationException($exception->getMessage());
        } catch (ExpiredException $exception) {
            throw new AuthenticationException($this->ci->lang->line('rest_jwt_expired_token'));
        } catch (\UnexpectedValueException $exception) {
            throw new AuthenticationException($this->ci->lang->line('rest_auth_failed'));
        }
    }

    private function httpServerAuth(): ?string
    {
        return $this->ci->input->server('HTTP_AUTHORIZATION') ?: $this->ci->input->server('HTTP_AUTHENTICATION');
    }

    private function apiKeyValue(): ?string
    {
        $apiKeyName = $this->config['api_key_name'];
        // Work out the name of the SERVER entry based on config
        $keyName = 'HTTP_' . \strtoupper(\str_replace('-', '_', $apiKeyName));
        // Find the key from server or arguments
        return \array_key_exists($apiKeyName, $this->request->getArgs())
            ? $this->request->getArgs()[$apiKeyName]
            : $this->ci->input->server($keyName);
    }
}
