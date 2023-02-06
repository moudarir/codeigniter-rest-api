<?php

namespace Moudarir\CodeigniterApi\Http;

use Moudarir\CodeigniterApi\Models\Api\ApiKeyLog;

class Logger
{

    /**
     * @var int|null
     */
    private ?int $log_id = null;

    /**
     * Logger constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param array $data
     * @return bool
     */
    public function add(array $data): bool
    {
        $this->log_id = (new ApiKeyLog())
            ->setKeyId($data['key_id'] ?? null)
            ->setUriString()
            ->setMethod($data['method'])
            ->setIpAddress()
            ->setAuthorized($data['authorized'])
            ->create();

        return $this->log_id !== null;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function update(array $data): bool
    {
        if (!empty($data) && $this->getLogId() !== null) {
            $entity = new ApiKeyLog();
            $updated = false;

            if (array_key_exists('response_time', $data)) {
                $entity->setResponseTime($data['response_time']);
                $updated = true;
            }
            if (array_key_exists('response_code', $data)) {
                $entity->setResponseCode($data['response_code']);
                $updated = true;
            }

            if ($updated == true) {
                return $entity->update($this->getLogId());
            }
        }

        return false;
    }

    /**
     * @return int|null
     */
    public function getLogId(): ?int
    {
        return $this->log_id;
    }
}
