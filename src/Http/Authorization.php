<?php

namespace Moudarir\CodeigniterApi\Http;

use Moudarir\CodeigniterApi\Helpers\StringHelper;
use Moudarir\CodeigniterApi\Models\Api\ApiKey;
use Moudarir\CodeigniterApi\Models\Users\Authentication;
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
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->ci = &get_instance();
        self::$request = $request;
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

        if (!empty($username) && !empty($password)) {
            try {
                $user = (new Authentication())->login($username, $password);
                $this->authorized = true;

                if (Config::ENABLE_API_KEY) {
                    $apiKeyName = Config::API_KEY_NAME;
                    // Work out the name of the SERVER entry based on config
                    $keyName = 'HTTP_' . strtoupper(str_replace('-', '_', $apiKeyName));
                    // Find the key from server or arguments
                    $key = array_key_exists($apiKeyName, self::$request->getArgs())
                        ? self::$request->getArgs()[$apiKeyName]
                        : $this->ci->input->server($keyName);

                    if (empty($key)) {
                        throw new Exception("Clé API manquante.", Config::HTTP_UNAUTHORIZED);
                    }

                    $apiKey = (new ApiKey())->find(null, [
                        'key' => $key,
                        'user_id' => $user->getId(),
                    ]);

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

                if ($this->authorized === true) {
                    $authData = [
                        'id' => $user->getId(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'email' => $user->getEmail(),
                        'role' => $user->getUserRole()->getName(),
                    ];

                    if (!empty(Config::GENERATE_JWT_TOKEN)) {
                        $generateJWT = false;
                        $uri_segment = self::$request->getUriString();
                        foreach (Config::GENERATE_JWT_TOKEN as $segment) {
                            if (StringHelper::isStringContains($segment, $uri_segment)) {
                                $generateJWT = true;
                                break;
                            }
                        }

                        if ($generateJWT === true) {
                            $secret = getenv("JWT_SECRET");
                            $payload = [
                                'iss' => 'http://example.org',
                                'aud' => 'http://example.com',
                                'iat' => 1356999524,
                                'nbf' => 1357000000,
                                'exp' => time() + (60 * 60),
                                'user' => $authData
                            ];
                            $authData['jwt_token'] = JWT::encode($payload, $secret, 'HS256');
                        }
                    }

                    $this->auth_data = $authData;
                }
            } catch (Exception $ex) {
                throw new Exception($ex->getMessage(), Config::HTTP_UNAUTHORIZED);
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
}
