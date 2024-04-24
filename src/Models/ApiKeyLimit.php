<?php

namespace Moudarir\CodeigniterApi\Models;

use Moudarir\CodeigniterApi\Helpers\Carbonize;

class ApiKeyLimit extends Model
{

    public int $key_id;

    public string $request;

    public int $counter;

    public int $started_at;

    protected string $table = 'api_key_limits';

    protected string $alias = 'akl';

    public function find(?int $id = null, ?array $options = null): ?self
    {
        if ((int)$id <= 0 && empty($options)) {
            return null;
        }

        $default = [
            'key_id' => null,
            'request' => null,
        ];
        $config = $this->setOptions($default, $options);
        $param = [];

        if ((int)$id > 0) {
            $param['where']['`'.$this->alias.'`.`id`'] = (int)$id;
        }
        if ((int)$config['key_id'] > 0) {
            $param['where']['`'.$this->alias.'`.`key_id`'] = (int)$config['key_id'];
        }
        if (!empty($config['request'])) {
            $param['where']['`'.$this->alias.'`.`request`'] = $config['request'];
        }

        return $this->findOne($param);
    }

    public function reset(?int $timestamp = null): bool
    {
        return $this->setCounter()->setStartedAt($timestamp)->setUpdatedAt()->update();
    }

    public function increaseCounter(): bool
    {
        return $this->setCounter($this->getCounter() + 1)->setUpdatedAt()->update();
    }

    /**
     * Getters
     */

    public function getKeyId(): int
    {
        return $this->key_id;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function getStartedAt(): int
    {
        return $this->started_at;
    }

    /**
     * Setters
     */

    public function setKeyId(int $key_id): self
    {
        $this->key_id = $key_id;
        return $this;
    }

    public function setRequest(string $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function setCounter(int $counter = 1): self
    {
        $this->counter = $counter;
        return $this;
    }

    public function setStartedAt(?int $started_at = null): self
    {
        $this->started_at = $started_at ?: (new Carbonize())->getTimestamp();
        return $this;
    }
}
