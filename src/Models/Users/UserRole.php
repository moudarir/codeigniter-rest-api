<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Models\TableFactory;
use Tightenco\Collect\Support\Collection;

class UserRole extends TableFactory
{

    /**
     * @var int
     */
    public int $user_id;

    /**
     * @var int
     */
    public int $role_id;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string|null
     */
    private ?string $description;

    /**
     * @var string|null
     */
    private ?string $dashboard;

    /**
     * @var string|null
     */
    private ?string $activation_method;

    /**
     * @var int|null
     */
    private ?int $manual_activation;

    /**
     * UserRole constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $default = ['table' => 'users_roles'];
        $config = array_merge($default, $config);
        parent::__construct($config);
    }

    /**
     * @param int $userId
     * @return self|null
     */
    public function find(int $userId): ?self
    {
        $alias = $this->getAlias();
        $rEntity = new Role();
        $rAls = $rEntity->getAlias();
        $rTbl = $rEntity->getTable();
        $param = [
            'joins' => [
                'role' => [
                    'fields' => '`'.$rAls.'`.`name`, `'.$rAls.'`.`description`, `'.$rAls.'`.`dashboard`, `'.$rAls.'`.`activation_method`, `'.$rAls.'`.`manual_activation`',
                    'table' => '`'.$rTbl.'` `'.$rAls.'`',
                    'type' => 'INNER',
                    'local' => '`'.$alias.'`.`role_id`',
                    'foreign' => '`'.$rAls.'`.`id`'
                ]
            ],
            'where' => [
                '`'.$alias.'`.`user_id`' => $userId
            ]
        ];

        return $this->findOne($param);
    }

    /**
     * @param array|null $options
     * @return Collection
     */
    public function collect(?array $options = null): Collection
    {
        $default = [
            'role_ids' => null,
            'user_ids' => null,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $rEntity = new Role();
        $rAls = $rEntity->getAlias();
        $rTbl = $rEntity->getTable();
        $param = [
            'joins' => [
                'role' => [
                    'fields' => '`'.$rAls.'`.`name`, `'.$rAls.'`.`description`, `'.$rAls.'`.`dashboard`, `'.$rAls.'`.`activation_method`, `'.$rAls.'`.`manual_activation`',
                    'table' => '`'.$rTbl.'` `'.$rAls.'`',
                    'type' => 'INNER',
                    'local' => '`'.$alias.'`.`role_id`',
                    'foreign' => '`'.$rAls.'`.`id`'
                ]
            ]
        ];

        if ($config['role_ids'] !== null) {
            $roleIds = is_array($config['role_ids']) ? $config['role_ids'] : [(int)$config['role_ids']];
            $param['where_in']['`'.$this->alias.'`.`role_id`'] = $roleIds;
        }
        if ($config['user_ids'] !== null) {
            $userIds = is_array($config['user_ids']) ? $config['user_ids'] : [(int)$config['user_ids']];
            $param['where_in']['`'.$this->alias.'`.`user_id`'] = $userIds;
        }
        if (array_key_exists('page', $config)) {
            $param['page'] = (int)$config['page'];
        }
        if (array_key_exists('limit', $config)) {
            $param['limit'] = (int)$config['limit'];
        }

        return $this->findAllCollection($param);
    }

    /**
     * @param int $user_id
     * @param int|null $role_id
     * @return bool
     */
    public function remove(int $user_id, ?int $role_id = null): bool
    {
        if ($user_id <= 0) {
            return false;
        }

        $whereClause = ['user_id' => $user_id];

        if ($role_id !== null) {
            $whereClause['role_id'] = $role_id;
        }

        return $this->delete($whereClause);
    }

    /**
     * Getters
     */

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getRoleId(): int
    {
        return $this->role_id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getDashboard(): ?string
    {
        return $this->dashboard;
    }

    /**
     * @return string|null
     */
    public function getActivationMethod(): ?string
    {
        return $this->activation_method;
    }

    /**
     * @return int|null
     */
    public function getManualActivation(): ?int
    {
        return $this->manual_activation;
    }

    /**
     * Setters
     */

    /**
     * @param int $user_id
     * @return self
     */
    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @param int $role_id
     * @return self
     */
    public function setRoleId(int $role_id): self
    {
        $this->role_id = $role_id;
        return $this;
    }
}
