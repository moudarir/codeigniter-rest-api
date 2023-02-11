<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Models\TableFactory;

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
                    'fields' => '`'.$rAls.'`.`name`',
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
     * @return UserRole[]|array
     */
    public function all(?array $options = null): array
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
                    'fields' => '`'.$rAls.'`.`name`',
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

        return $this->findAll($param) ?: [];
    }

    /**
     * @param array $data
     * @return int|null
     */
    public function add(array $data): ?int
    {
        if (!array_key_exists('created_at', $data) || !array_key_exists('updated_at', $data)) {
            $currentDate = date("Y-m-d H:i:s", time());
            if (!array_key_exists('created_at', $data)) {
                $data['created_at'] = $currentDate;
            }
            if (!array_key_exists('updated_at', $data)) {
                $data['updated_at'] = $currentDate;
            }
        }

        self::getDatabase()->insert($this->getTable(), $data, true);

        return self::getDatabase()->affected_rows() > 0 ? self::getDatabase()->insert_id() : null;
    }

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
}
