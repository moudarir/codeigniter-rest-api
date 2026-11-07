<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Exceptions\ApiException;

final class ApiLogger
{

    private array $log_data = [];

    public function __construct(private readonly ApiConfig $apiConfig)
    {}

    public function push(array $data): self
    {
        if (array_key_exists('client_id', $data)) {
            $this->log_data['client_id'] = $data['client_id'];
        }
        if (array_key_exists('method', $data)) {
            $this->log_data['method'] = $data['method'];
        }
        if (array_key_exists('response_time', $data)) {
            $this->log_data['response_time'] = $data['response_time'];
        }
        if (array_key_exists('response_code', $data)) {
            $this->log_data['response_code'] = $data['response_code'];
        }
        if (array_key_exists('authorized', $data)) {
            $this->log_data['authorized'] = $data['authorized'];
        }

        return $this;
    }

    /**
     * @throws ApiException
     */
    public function flush(): bool
    {
        if ($this->log_data === []) {
            return false;
        }

        if (
            array_key_exists('authorized', $this->log_data) &&
            $this->log_data['authorized'] === 0 &&
            $this->apiConfig->logOnlyAuthorized === true
        ) {
            return false;
        }

        return new $this->apiConfig->loggerClassName()->save($this->log_data);
    }
}
