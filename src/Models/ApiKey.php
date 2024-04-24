<?php

namespace Moudarir\CodeigniterApi\Models;

use Moudarir\CodeigniterApi\Helpers\CommonHelper;

class ApiKey extends Model
{

    public int $user_id;

    public string $key;

    public string $username;

    public string $password;

    public ?int $limits;

    public ?int $reset_limits_after;

    public ?string $ip_addresses;

    protected string $table = 'api_keys';

    protected string $alias = 'ak';

    const API_KEY_LENGTH = 40;

    const USERNAME_LENGTH = 8;

    const PASSWORD_LENGTH = 16;

    public function find(?int $id = null, ?array $options = null): ?array
    {
        if ((int)$id <= 0 && empty($options)) {
            return null;
        }

        $default = [
            'key' => null,
            'username' => null,
            'password' => null,
        ];
        $config = $this->setOptions($default, $options);
        $param = [];

        if ((int)$id > 0) {
            $param['where']['`'.$this->alias.'`.`id`'] = (int)$id;
        }
        if ($config['key'] !== null) {
            $param['where']['`'.$this->alias.'`.`key`'] = $config['key'];
        }
        if ($config['username'] !== null) {
            $param['where']['`'.$this->alias.'`.`username`'] = $config['username'];
        }
        if ($config['password'] !== null) {
            $param['where']['`'.$this->alias.'`.`password`'] = $config['password'];
        }

        return $this->fetchOne($param);
    }

    public function verify(string $username, string $password, ?string $key = null): ?array
    {
        if (empty($username) || empty($password)) {
            return null;
        }

        return $this->find(null, [
            'username' => $username,
            'password' => $password,
            'key' => $key,
        ]);
    }

    /**
     * Getters
     */

    public function getKey(): string
    {
        return $this->key;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getLimits(): ?int
    {
        return $this->limits;
    }

    public function getResetLimitsAfter(): ?int
    {
        return $this->reset_limits_after;
    }

    public function getIpAddresses(): ?string
    {
        return $this->ip_addresses;
    }

    /**
     * Setters
     */

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function setKey(): self
    {
        $this->key = $this->getUniqueToken('key', ['length' => self::API_KEY_LENGTH]);
        return $this;
    }

    public function setUsername(): self
    {
        $this->username = $this->getUniqueToken('username', ['length' => self::USERNAME_LENGTH]);
        return $this;
    }

    public function setPassword(): self
    {
        $this->password = CommonHelper::generateToken(self::PASSWORD_LENGTH);
        return $this;
    }

    public function setLimits(?int $limits = null): self
    {
        $this->limits = $limits;
        return $this;
    }

    public function setResetLimitsAfter(?int $reset_limits_after = null): self
    {
        $this->reset_limits_after = $reset_limits_after;
        return $this;
    }

    public function setIpAddresses(?string $ip_addresses = null): self
    {
        $this->ip_addresses = $ip_addresses;
        return $this;
    }
}
