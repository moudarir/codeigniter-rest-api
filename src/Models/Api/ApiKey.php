<?php

namespace Moudarir\CodeigniterApi\Models\Api;

use Moudarir\CodeigniterApi\Http\Helpers;
use Moudarir\CodeigniterApi\Models\TableFactory;

class ApiKey extends TableFactory
{

    /**
     * API Key Length
     */
    const API_KEY_LENGTH = 40;

    /**
     * API Key Length
     */
    const USERNAME_LENGTH = 8;

    /**
     * API Key Length
     */
    const PASSWORD_LENGTH = 16;

    /**
     * @var int|null
     */
    public ?int $user_id;

    /**
     * @var string
     */
    public string $key;

    /**
     * @var string
     */
    public string $username;

    /**
     * @var string
     */
    public string $password;

    /**
     * @var string|null
     */
    public ?string $ip_addresses;

    /**
     * ApiKey constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @param int|null $id
     * @param array|null $options
     * @return self|null
     */
    public function find(?int $id = null, ?array $options = null): ?self
    {
        if ((int)$id <= 0 && $options === null) {
            return null;
        }

        $default = [
            'user_id'  => null,
            'key'      => null,
            'username' => null,
            'password' => null,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ((int)$id > 0) {
            $param['where']['`'.$alias.'`.`id`'] = (int)$id;
        }
        if ((int)$config['user_id'] > 0) {
            $param['where']['`'.$alias.'`.`user_id`'] = (int)$config['user_id'];
        }
        if (!empty($config['key'])) {
            $param['where']['`'.$alias.'`.`key`'] = $config['key'];
        }
        if ($config['username'] !== null && $config['username'] !== '') {
            $param['where']['`'.$alias.'`.`username`'] = $config['username'];
        }
        if ($config['password'] !== null && $config['password'] !== '') {
            $param['where']['`'.$alias.'`.`password`'] = $config['password'];
        }

        return $this->findOne($param);
    }

    /**
     * @param array|null $options
     * @return ApiKey[]|array
     */
    public function all(?array $options = null): array
    {
        $default = ['ids' => null];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ($config['ids'] !== null) {
            $ids = is_array($config['ids']) ? $config['ids'] : [(int)$config['ids']];
            $param['where_in']['`'.$alias.'`.`id`'] = $ids;
        }

        return $this->findAll($param) ?: [];
    }

    /**
     * @param array $ids
     * @return int
     */
    public function reset(array $ids): int
    {
        $finished = 0;

        if (empty($ids)) {
            return $finished;
        }

        self::getDatabase()->trans_start();
        $apiKeys = $this->all(['ids' => $ids]);
        foreach ($apiKeys as $apiKey) {
            $apiKey->setKey()->setUsername()->setPassword();

            if ($apiKey->update()) {
                $finished++;
            }
        }
        self::getDatabase()->trans_complete();

        return $finished;
    }

    /**
     * @param array $ids
     * @return int
     */
    public function remove(array $ids): int
    {
        $finished = 0;

        if (empty($ids)) {
            return $finished;
        }

        $finished = 0;
        self::getDatabase()->trans_start();
        $apiKeys = $this->all(['ids' => $ids]);
        foreach ($apiKeys as $apiKey) {
            if ($apiKey->delete()) {
                $finished++;
            }
        }
        self::getDatabase()->trans_complete();

        return $finished;
    }

    /**
     * Getters
     */

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getIpAddresses(): ?string
    {
        return $this->ip_addresses;
    }

    /**
     * Setters
     */

    /**
     * @param int|null $user_id
     * @return self
     */
    public function setUserId(?int $user_id = null): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return self
     */
    public function setKey(): self
    {
        $this->key = $this->generateToken(self::API_KEY_LENGTH);
        return $this;
    }

    /**
     * @return self
     */
    public function setUsername(): self
    {
        $this->username = $this->generateToken(self::USERNAME_LENGTH, 'username');
        return $this;
    }

    /**
     * @return self
     */
    public function setPassword(): self
    {
        $this->password = $this->generateToken(self::PASSWORD_LENGTH, 'password');
        return $this;
    }

    /**
     * @param string|null $ip_addresses
     * @return self
     */
    public function setIpAddresses(?string $ip_addresses = null): self
    {
        $this->ip_addresses = $ip_addresses;
        return $this;
    }

    /**
     * @param int $length
     * @param string $field
     * @param array $exclude
     * @return string
     */
    private function generateToken(int $length, string $field = 'key', array $exclude = []): string
    {
        $token = Helpers::generateToken($length, 'alnum');

        self::getDatabase()->where($field, $token);

        if (!empty($exclude)) {
            self::getDatabase()->where_not_in($field, $exclude);
        }

        $found = self::getDatabase()->count_all_results($this->getTable());

        if ($found > 0) {
            $exclude[] = $token;
            return $this->generateToken($length, $field, $exclude);
        }

        return $token;
    }
}
