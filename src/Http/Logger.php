<?php

namespace Moudarir\CodeigniterApi\Http;

use Moudarir\CodeigniterApi\Exceptions\DatabaseCreateException;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use Moudarir\CodeigniterApi\Models\ApiKeyLog;

class Logger
{

    private ApiKeyLog $api_key_log;

    public function push(array $data): self
    {
        if (!isset($this->api_key_log)) {
            $this->api_key_log = new ApiKeyLog();
        }

        if (\array_key_exists('key_id', $data)) {
            $this->api_key_log->setKeyId($data['key_id']);
        }
        if (\array_key_exists('method', $data)) {
            $this->api_key_log->setMethod($data['method']);
        }
        if (\array_key_exists('response_time', $data)) {
            $this->api_key_log->setResponseTime($data['response_time']);
        }
        if (\array_key_exists('response_code', $data)) {
            $this->api_key_log->setResponseCode($data['response_code']);
        }
        if (\array_key_exists('authorized', $data)) {
            $this->api_key_log->setAuthorized($data['authorized']);
        }

        return $this;
    }

    public function flush(): bool
    {
        if (isset($this->api_key_log)) {
            try {
                $this->api_key_log->setUriString()->setIpAddress()->create();
                return true;
            } catch (DatabaseCreateException $exception) {
                CommonHelper::logException($exception);
            }
        }

        return false;
    }
}
