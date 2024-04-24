<?php

namespace Moudarir\CodeigniterApi\Models;

class ApiKeyLog extends Model
{

    public ?int $key_id;

    public string $uri_string;

    public string $method;

    public string $ip_address;

    public ?float $response_time;

    public ?int $response_code;

    public int $authorized;

    protected string $table = 'api_key_logs';

    protected string $alias = 'akl';

    public function setKeyId(?int $key_id = null): self
    {
        $this->key_id = $key_id;
        return $this;
    }

    public function setUriString(): self
    {
        $this->uri_string = $this->uri->uri_string();
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function setIpAddress(): self
    {
        $this->ip_address = $this->input->ip_address();
        return $this;
    }

    public function setResponseTime(?float $response_time = null): self
    {
        $this->response_time = $response_time;
        return $this;
    }

    public function setResponseCode(?int $response_code = null): self
    {
        $this->response_code = $response_code;
        return $this;
    }

    public function setAuthorized(int $authorized): self
    {
        $this->authorized = $authorized;
        return $this;
    }
}
