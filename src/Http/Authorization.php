<?php

namespace Moudarir\CodeigniterApi\Http;

use Moudarir\CodeigniterApi\Models\Api\ApiKey;
use CI_Controller;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
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
     * @var ApiKey|null
     */
    private ?ApiKey $apiKey = null;

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
            throw new Exception("Clé du jeton manquante.", Config::HTTP_UNAUTHORIZED);
        }

        if (strpos(strtolower($http_auth), 'basic') === 0) {
            $this->basic($http_auth);
        }

        if (strpos(strtolower($http_auth), 'bearer') === 0) {
            $this->bearer($http_auth);
        }
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
     * @return ApiKey|null
     */
    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey ?? null;
    }

    /**
     * @param ApiKey|null $apiKey
     * @return self
     */
    private function setApiKey(?ApiKey $apiKey = null): self
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
            throw new Exception("Clé API manquante.", Config::HTTP_UNAUTHORIZED);
        }

        if (empty($username) || empty($password)) {
            throw new Exception("Erreur de connexion.", Config::HTTP_UNAUTHORIZED);
        }

        $options = [
            'username' => $username,
            'password' => $password,
        ];

        if ($this->config['enable_api_key'] === true) {
            $options['key'] = $key;
        }

        $apiKey = (new ApiKey())->find(null, $options);

        if ($apiKey !== null) {
            $this->authorized = true;
            $this->setApiKey($apiKey);

            // If "is private key" is enabled, compare the ip address with the list
            // of valid ip addresses stored in the database
            if ($apiKey->getIpAddresses() !== null) {
                // multiple ip addresses must be separated using a comma
                $ipaList = explode(',', $apiKey->getIpAddresses());
                $ipAddress = $this->ci->input->ip_address();
                $ips = array_filter($ipaList, fn ($ipa) => (trim($ipa) === $ipAddress));

                // There is a match, set the the "authorized" value to FALSE
                if (!empty($ips)) {
                    $this->authorized = false;
                    throw new Exception("Adresse IP non autorisée.", Config::HTTP_UNAUTHORIZED);
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
        $token = substr($http_auth, 7);

        try {
            $authData = (array) JWT::decode($token, new Key(getenv("JWT_SECRET"), 'HS256'));
            $this->auth_data = (array) $authData;
            $this->authorized = true;
        } catch (LogicException $e) {
            // errors having to do with environmental setup or malformed JWT Keys
            throw new Exception($e->getMessage(), Config::HTTP_UNAUTHORIZED);
        } catch (ExpiredException $e) {
            // provided JWT is trying to be used after "exp" claim.
            throw new Exception("Clé du jeton expirée.", Config::HTTP_UNAUTHORIZED);
        } catch (UnexpectedValueException $e) {
            // errors having to do with JWT signature and claims
            throw new Exception($e->getMessage(), Config::HTTP_UNAUTHORIZED);
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
