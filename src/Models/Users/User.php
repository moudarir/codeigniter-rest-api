<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Http\Helpers;
use Moudarir\CodeigniterApi\Models\TableFactory;

class User extends TableFactory
{

    /**
     * @var string
     */
    public string $firstname;

    /**
     * @var string
     */
    public string $lastname;

    /**
     * @var string
     */
    public string $email;

    /**
     * @var string
     */
    public string $password;

    /**
     * @var UserRole|null
     */
    private ?UserRole $user_role;

    /**
     * User constructor.
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
        if ($id === null && $options === null) {
            return null;
        }

        $default = [
            'email' => null,
            'role' => false,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ($id !== null) {
            $param['where']['`'.$alias.'`.`id`'] = $id;
        }
        if ($config['email'] !== null) {
            $param['where']['`'.$alias.'`.`email`'] = $config['email'];
        }

        $item = $this->findOne($param);

        if ($item !== null) {
            if ($config['role'] !== false) {
                $item->setUserRole((new UserRole())->find($item->getId()));
            }
        }

        return $item;
    }

    /**
     * @param array|null $options
     * @return User[]|array
     */
    public function all(?array $options = null): array
    {
        $default = [
            'ids' => null,
            'in_roles' => null,
            'role' => false,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ($config['ids'] !== null) {
            $ids = is_array($config['ids']) ? $config['ids'] : [(int)$config['ids']];
            $param['where_in']['`'.$alias.'`.`id`'] = $ids;
        }
        if ($config['in_roles'] !== null) {
            $inRoles = is_array($config['in_roles']) ? $config['in_roles'] : [$config['in_roles']];
            $urEntity = new UserRole();
            $rEntity = new Role();
            $urTbl = $urEntity->getTable();
            $urAls = $urEntity->getAlias();
            $rTbl = $rEntity->getTable();
            $rAls = $rEntity->getAlias();
            $param['joins'] = [
                'user_role' => [
                    'table' => '`'.$urTbl.'` `'.$urAls.'`',
                    'type' => 'INNER',
                    'local' => '`'.$alias.'`.`id`',
                    'foreign' => '`'.$urAls.'`.`user_id`',
                ],
                'role' => [
                    'table' => '`'.$rTbl.'` `'.$rAls.'`',
                    'type' => 'INNER',
                    'local' => '`'.$urAls.'`.`role_id`',
                    'foreign' => '`'.$rAls.'`.`id` AND `'.$rAls.'`.`name` IN (\''.implode("','", $inRoles).'\')',
                ],
            ];
        }
        if (array_key_exists('page', $config)) {
            $param['page'] = (int)$config['page'];
        }
        if (array_key_exists('limit', $config)) {
            $param['limit'] = (int)$config['limit'];
        }

        $items = $this->findAll($param);

        if (!empty($items)) {
            $ids = array_keys(array_column($items, 'id', 'id'));

            if ($config['role'] !== false) {
                $urOptions = is_array($config['role'])
                    ? $this->setOptions(['user_ids' => $ids], $config['role'])
                    : ['user_ids' => $ids];
                $roles = Helpers::groupBy('user_id', (new UserRole())->all($urOptions));

                foreach ($items as $item) {
                    $id = $item->getId();

                    if (!empty($roles)) {
                        $userRole = array_key_exists($id, $roles) ? $roles[$id][0] : null;
                        $item->setUserRole($userRole);
                    }
                }
            }
        }

        return $items ?: [];
    }

    /**
     * @param array|null $options
     * @return int
     */
    public function count(?array $options = null): int
    {
        $default = [
            'ids' => null,
            'in_roles' => null,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ($config['ids'] !== null) {
            $ids = is_array($config['ids']) ? $config['ids'] : [(int)$config['ids']];
            $param['where_in']['`'.$alias.'`.`id`'] = $ids;
        }
        if ($config['in_roles'] !== null) {
            $inRoles = is_array($config['in_roles']) ? $config['in_roles'] : [$config['in_roles']];
            $urEntity = new UserRole();
            $rEntity = new Role();
            $urTbl = $urEntity->getTable();
            $urAls = $urEntity->getAlias();
            $rTbl = $rEntity->getTable();
            $rAls = $rEntity->getAlias();
            $param['joins'] = [
                'user_role' => [
                    'table' => '`'.$urTbl.'` `'.$urAls.'`',
                    'type' => 'INNER',
                    'local' => '`'.$alias.'`.`id`',
                    'foreign' => '`'.$urAls.'`.`user_id`',
                ],
                'role' => [
                    'table' => '`'.$rTbl.'` `'.$rAls.'`',
                    'type' => 'INNER',
                    'local' => '`'.$urAls.'`.`role_id`',
                    'foreign' => '`'.$rAls.'`.`id` AND `'.$rAls.'`.`name` IN (\''.implode("','", $inRoles).'\')',
                ],
            ];
        }

        return $this->countAll($param);
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
     * @param User|null $_user
     * @return array
     */
    public function normalize(?User $_user = null): array
    {
        $user = $_user ?: $this;
        $data = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
        ];

        if ($user->getUserRole() !== null) {
            $data['role'] = $user->getUserRole()->getName();
        }

        return $data;
    }

    /**
     * @param array|iterable $users
     * @return array[]
     */
    public function normalizeAll($users): array
    {
        $data = [];

        if ((is_array($users) && !empty($users)) || is_iterable($users)) {
            foreach ($users as $user) {
                if ($user instanceof User) {
                    $data[] = $this->normalize($user);
                }
            }
        }

        return $data;
    }

    /**
     * @return string|null
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return UserRole|null
     */
    public function getUserRole(): ?UserRole
    {
        return $this->user_role ?? null;
    }

    /**
     * @param UserRole|null $user_role
     * @return self
     */
    public function setUserRole(?UserRole $user_role = null): self
    {
        $this->user_role = $user_role;

        return $this;
    }
}
