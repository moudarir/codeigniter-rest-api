<?php

namespace Moudarir\CodeigniterApi\Models;

class User extends Model
{

    public string $firstname;

    public string $lastname;

    public string $email;

    public string $password;

    protected string $table = 'users';

    protected string $alias = 'u';

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['`'.$this->alias.'`.`id`' => $id]);
    }

    public function all(array $options = []): array
    {
        $param = [];

        if (\array_key_exists('page', $options)) {
            $param['page'] = $options['page'];
        }
        if (\array_key_exists('limit', $options)) {
            $param['limit'] = $options['limit'];
        }

        return $this->fetchAll($param);
    }

    public function count(): int
    {
        return $this->countAll();
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
}
