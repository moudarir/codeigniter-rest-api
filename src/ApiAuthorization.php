<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Enums\AuthMethod;
use Moudarir\CIRestApi\Exceptions\ApiException;
use Moudarir\CIRestApi\Helpers\Common;
use Moudarir\CIRestApi\Interfaces\ApiClientInterface;
use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use UnexpectedValueException;

final readonly class ApiAuthorization
{

    public function __construct(private ApiConfig $apiConfig, private ApiRequest $apiRequest)
    {
    }

    /**
     * @throws ApiException
     */
    public function check(): ApiClientInterface
    {
        return match ($this->apiConfig->authMethod) {
            AuthMethod::BASIC => $this->basic(),
            AuthMethod::BEARER => $this->bearer(),
        };
    }

    /**
     * @throws ApiException
     */
    private function basic(): ApiClientInterface
    {
        if (($decodedAuth = base64_decode(substr($this->httpServerAuth(), 6), true)) === false) {
            throw ApiException::authenticationFailed();
        }

        $credentials = explode(':', $decodedAuth, 2);

        if (count($credentials) !== 2) {
            throw ApiException::authenticationFailed();
        }

        [$username, $password] = $credentials;
        $key = $this->apiKeyValue();

        if ($this->apiConfig->enableApiKey === true && empty($key)) {
            throw ApiException::missingApiKey();
        }

        if (empty($username) || empty($password)) {
            throw ApiException::authenticationFailed();
        }

        $client = new $this->apiConfig->authenticationClassName()->basicVerification([
            'username' => $username,
            'password' => $password,
            'key' => $this->apiConfig->enableApiKey === true ? $key : null,
        ]);

        if ($client === null) {
            throw ApiException::authenticationFailed();
        }

        self::checkIpAddress($client->getIpAddresses());

        return $client;
    }

    /**
     * @throws ApiException
     */
    private function bearer(): ApiClientInterface
    {
        try {
            $token = substr($this->httpServerAuth(), 7);
            $data = (array) JWT::decode(
                $token,
                new Key(
                    $this->apiConfig->authenticationJWTSecret,
                    $this->apiConfig->authenticationJWTAlgo
                )
            );

            $client = array_key_exists('client_id', $data) && (int)$data['client_id'] > 0
                ? new $this->apiConfig->authenticationClassName()->bearerVerification($data['client_id'])
                : null;

            if ($client === null) {
                throw ApiException::authenticationFailed();
            }

            self::checkIpAddress($client->getIpAddresses());

            return $client;
        } catch (InvalidArgumentException|DomainException $exception) {
            throw ApiException::internalServerError($exception);
        } catch (SignatureInvalidException $exception) {
            throw ApiException::invalidTokenSignature($exception);
        } catch (BeforeValidException $exception) {
            throw ApiException::beforeValidError($exception->getMessage());
        } catch (ExpiredException $exception) {
            throw ApiException::expiredTokenKey($exception);
        } catch (UnexpectedValueException $exception) {
            throw ApiException::authenticationFailed($exception);
        }
    }

    /**
     * @throws ApiException
     */
    private function httpServerAuth(): string
    {
        $httpAuth = $this->apiRequest->server('HTTP_AUTHORIZATION') ?: $this->apiRequest->server('HTTP_AUTHENTICATION');

        if ($httpAuth === null) {
            throw ApiException::authenticationFailed();
        }

        return $httpAuth;
    }

    private function apiKeyValue(): ?string
    {
        $apiKeyName = $this->apiConfig->apiKeyName;
        // Work out the name of the SERVER entry based on config
        $keyName = 'HTTP_' . strtoupper(str_replace('-', '_', $apiKeyName));
        // Find the key from server or arguments
        $headers = $this->apiRequest->headers();
        return array_key_exists($apiKeyName, $headers)
            ? $headers[$apiKeyName]
            : $this->apiRequest->server($keyName);
    }

    /**
     * @throws ApiException
     */
    private static function checkIpAddress(?string $ipAddresses = null): void
    {
        if ($ipAddresses !== null) {
            $ipAddress = Common::ipAddress();
            if (!in_array($ipAddress, explode(',', $ipAddresses))) {
                throw ApiException::ipAddressNotAllowed($ipAddress);
            }
        }
    }
}
