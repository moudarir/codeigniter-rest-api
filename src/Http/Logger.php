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
        $this->log_id = (new ApiKeyLog())->add($data);
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

            return $entity->edit($this->getLogId(), $data);
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
