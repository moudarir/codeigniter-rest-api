<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Enums\HttpMethod;
use Moudarir\CIRestApi\Enums\StatusCode;
use Moudarir\CIRestApi\Exceptions\ApiException;
use Moudarir\CIRestApi\Interfaces\ApiClientInterface;

class ApiController extends \CI_Controller
{

    protected ?ApiLogger $logger = null;

    protected ApiRequest $request;

    protected ApiResponse $response;

    protected ApiClientInterface $client;

    protected bool $disable_authentication = false;

    private float $response_start_time;

    public function __construct(protected readonly ApiConfig $apiConfig = new ApiConfig())
    {
        if (class_exists('CI_Controller', false) === false) {
            new ApiResponse($this->apiConfig)->response([
                'error' => true,
                'message' => "Erreur Interne du serveur (Constructor issue)."
            ], StatusCode::INTERNAL_SERVER_ERROR);
        }

        parent::__construct();

        try {
            $this->request = new ApiRequest($this->apiConfig);

            if ($this->apiConfig->enableLogging === true) {
                $this->response_start_time = microtime(true);
                $this->logger = new ApiLogger($this->apiConfig);
            }

            $this->response = new ApiResponse($this->apiConfig, $this->logger);
        } catch (ApiException $exception) {
            new ApiResponse($this->apiConfig)->response([
                'error' => true,
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }

    public function __destruct()
    {
        if ($this->apiConfig->enableLogging === true) {
            $this->logger?->push(['response_time' => (microtime(true) - $this->response_start_time)])->flush();
        }
    }

    /**
     * @see _remap()
     */
    public function _remap(string $called_method, array $arguments = []): void
    {
        $httpMethod = $this->request->httpMethod;

        // Sure it exists, but can they do anything with it?
        if (!method_exists($this, $called_method)) {
            if ($this->apiConfig->enableLogging === true) {
                $this->logger?->push([
                    'client_id' => null,
                    'method' => $httpMethod->value,
                    'authorized' => 0,
                ]);
            }

            $this->response->methodNotAllowed("Chemin inconnu.");
        }

        if ($this->apiConfig->enableAuthentication === true && $this->disable_authentication === false) {
            try {
                $this->client = new ApiAuthorization($this->apiConfig, $this->request)->check();

                if ($this->apiConfig->enableLimits === true) {
                    new ApiLimitation($this->client, $this->apiConfig)
                        ->check($called_method, $httpMethod->value);
                }

                if ($this->apiConfig->enableLogging === true) {
                    $this->logger?->push([
                        'client_id' => $this->client->getId(),
                        'method' => $httpMethod->value,
                        'authorized' => 1,
                    ]);
                }
            } catch (ApiException $exception) {
                if ($this->apiConfig->enableLogging === true) {
                    $this->logger?->push([
                        'client_id' => null,
                        'method' => $httpMethod->value,
                        'authorized' => 0,
                    ]);
                }

                // fix cross site to option request error
                if ($httpMethod === HttpMethod::OPTIONS) {
                    exit();
                }

                $this->response->response([
                    'error' => true,
                    'message' => $exception->getMessage(),
                ], $exception->getCode());
            }
        }

        call_user_func_array([$this, $called_method], $arguments);
    }
}
