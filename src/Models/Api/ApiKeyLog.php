<?php

namespace Moudarir\CodeigniterApi\Models\Api;

use Moudarir\CodeigniterApi\Models\TableFactory;

class ApiKeyLog extends TableFactory
{

    /**
     * @var int|null
     */
    public ?int $key_id;

    /**
     * @var string
     */
    public string $uri_string;

    /**
     * @var string
     */
    public string $method;

    /**
     * @var string
     */
    public string $ip_address;

    /**
     * @var float|null
     */
    public ?float $response_time;

    /**
     * @var int
     */
    public int $authorized;

    /**
     * @var int|null
     */
    public int $response_code;

    /**
     * ApiKeyLog constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $default = ['table' => 'api_key_logs'];
        $config = array_merge($default, $config);
        parent::__construct($config);
    }

    /**
     * Getters
     */

    /**
     * @return int|null
     */
    public function getKeyId(): ?int
    {
        return $this->key_id;
    }

    /**
     * @return string
     */
    public function getUriString(): string
    {
        return $this->uri_string;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ip_address;
    }

    /**
     * @return float|null
     */
    public function getResponseTime(): ?float
    {
        return $this->response_time;
    }

    /**
     * @return int
     */
    public function getAuthorized(): int
    {
        return $this->authorized;
    }

    /**
     * @return int|null
     */
    public function getResponseCode(): ?int
    {
        return $this->response_code;
    }

    /**
     * Setters
     */

    /**
     * @param int|null $key_id
     * @return self
     */
    public function setKeyId(?int $key_id): self
    {
        $this->key_id = $key_id;
        return $this;
    }

    /**
     * @return self
     */
    public function setUriString(): self
    {
        $this->uri_string = $this->uri->uri_string();
        return $this;
    }

    /**
     * @param string $method
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return self
     */
    public function setIpAddress(): self
    {
        $this->ip_address = $this->input->ip_address();
        return $this;
    }

    /**
     * @param float|null $response_time
     * @return self
     */
    public function setResponseTime(?float $response_time = null): self
    {
        $this->response_time = $response_time;
        return $this;
    }

    /**
     * @param int $authorized
     * @return self
     */
    public function setAuthorized(int $authorized): self
    {
        $this->authorized = $authorized;
        return $this;
    }

    /**
     * @param int|null $response_code
     * @return self
     */
    public function setResponseCode(?int $response_code = null): self
    {
        $this->response_code = $response_code;
        return $this;
    }
}
