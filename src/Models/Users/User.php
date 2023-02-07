<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Models\TableFactory;
use Tightenco\Collect\Support\Collection;

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
     * @return Collection
     */
    public function collect(?array $options = null): Collection
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

        $collection = $this->findAllCollection($param);

        if ($collection->isNotEmpty()) {
            $ids = $collection->keyBy('id')->keys()->all();
            $roles = [];

            if ($config['role'] !== false) {
                $ugOptions = is_array($config['role'])
                    ? $this->setOptions(['user_ids' => $ids], $config['role'])
                    : ['user_ids' => $ids];
                $roles = (new UserRole())->collect($ugOptions)->groupBy('user_id')->toArray();
            }

            $collection->each(function (User $user) use ($roles) {
                $id = $user->getId();

                if (!empty($roles)) {
                    $userRole = array_key_exists($id, $roles) ? $roles[$id][0] : null;
                    $user->setUserRole($userRole);
                }
            });
        }

        return $collection;
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
     * Getters
     */

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
     * Setters
     */

    /**
     * @param string $firstname
     * @return self
     */
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @param string $lastname
     * @return self
     */
    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
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
