<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Models\TableFactory;
use Tightenco\Collect\Support\Collection;

class User extends TableFactory
{

    /**
     * @var int
     */
    public int $gender_id;

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
     * @var string|null
     */
    public ?string $phone;

    /**
     * @var string|null
     */
    public ?string $country_iso;

    /**
     * @var string|null
     */
    public ?string $hometown;

    /**
     * @var string|null
     */
    public ?string $position_held;

    /**
     * @var string|null
     */
    public ?string $activation_selector;

    /**
     * @var string|null
     */
    public ?string $activation_code;

    /**
     * @var string|null
     */
    public ?string $forgotten_password_selector;

    /**
     * @var string|null
     */
    public ?string $forgotten_password_code;

    /**
     * @var int|null
     */
    public ?int $forgotten_password_time;

    /**
     * @var string|null
     */
    public ?string $remember_selector;

    /**
     * @var string|null
     */
    public ?string $remember_code;

    /**
     * @var int
     */
    public int $active;

    /**
     * @var string|null
     */
    public ?string $ip_address;

    /**
     * @var int|null
     */
    public ?int $last_login;

    /**
     * @var string|null
     */
    private ?string $user_fullname;

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
            'identity' => null,
            'activation_selector' => null,
            'forgotten_password_selector' => null,
            'remember_selector' => null,
            'active' => null,
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
        if ($config['identity'] !== null) {
            $param['where']['`'.$alias.'`.`email`'] = $config['identity'];
        }
        if ($config['activation_selector'] !== null) {
            $param['where']['`'.$alias.'`.`activation_selector`'] = $config['activation_selector'];
        }
        if ($config['forgotten_password_selector'] !== null) {
            $param['where']['`'.$alias.'`.`forgotten_password_selector`'] = $config['forgotten_password_selector'];
        }
        if ($config['remember_selector'] !== null) {
            $param['where']['`'.$alias.'`.`remember_selector`'] = $config['remember_selector'];
        }
        if ($config['active'] !== null) {
            $param['where']['`'.$alias.'`.`active`'] = (int)$config['active'];
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
            'active' => $user->getActive(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'position_held' => $user->getPositionHeld() ?: "Non défini",
            'phone' => $user->getPhone(),
            'hometown' => $user->getHometown(),
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
     * @return int|null
     */
    public function getGenderId(): int
    {
        return $this->gender_id;
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
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getCountryIso(): ?string
    {
        return $this->country_iso ?? null;
    }

    /**
     * @return string|null
     */
    public function getHometown(): ?string
    {
        return $this->hometown;
    }

    /**
     * @return string|null
     */
    public function getPositionHeld(): ?string
    {
        return $this->position_held;
    }

    /**
     * @return string|null
     */
    public function getActivationSelector(): ?string
    {
        return $this->activation_selector;
    }

    /**
     * @return string|null
     */
    public function getActivationCode(): ?string
    {
        return $this->activation_code;
    }

    /**
     * @return string|null
     */
    public function getForgottenPasswordSelector(): ?string
    {
        return $this->forgotten_password_selector;
    }

    /**
     * @return string|null
     */
    public function getForgottenPasswordCode(): ?string
    {
        return $this->forgotten_password_code;
    }

    /**
     * @return int|null
     */
    public function getForgottenPasswordTime(): ?int
    {
        return $this->forgotten_password_time;
    }

    /**
     * @return string|null
     */
    public function getRememberSelector(): ?string
    {
        return $this->remember_selector;
    }

    /**
     * @return string|null
     */
    public function getRememberCode(): ?string
    {
        return $this->remember_code;
    }

    /**
     * @return int
     */
    public function getActive(): ?int
    {
        return $this->active ?? 0;
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * @return int|null
     */
    public function getLastLogin(): ?int
    {
        return ($this->last_login ?? null);
    }

    /**
     * @return string|null
     */
    public function getUserFullname(): ?string
    {
        if (!isset($this->user_fullname) && $this->getId() !== null) {
            $this->user_fullname = ucwords($this->getFirstname()).' '.strtoupper($this->getLastname());
        }

        return $this->user_fullname ?? null;
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
     * @param int $gender_id
     * @return self
     */
    public function setGenderId(int $gender_id): self
    {
        $this->gender_id = $gender_id;
        return $this;
    }

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
     * @param string|null $phone
     * @return self
     */
    public function setPhone(?string $phone = null): self
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @param string|null $country_iso
     * @return self
     */
    public function setCountryIso(?string $country_iso = null): self
    {
        $this->country_iso = $country_iso;
        return $this;
    }

    /**
     * @param string|null $hometown
     * @return self
     */
    public function setHometown(?string $hometown = null): self
    {
        $this->hometown = $hometown;
        return $this;
    }

    /**
     * @param string|null $position_held
     * @return self
     */
    public function setPositionHeld(?string $position_held = null): self
    {
        $this->position_held = $position_held;
        return $this;
    }

    /**
     * @param string|null $activation_selector
     * @return self
     */
    public function setActivationSelector(?string $activation_selector = null): self
    {
        $this->activation_selector = $activation_selector;
        return $this;
    }

    /**
     * @param string|null $activation_code
     * @return self
     */
    public function setActivationCode(?string $activation_code = null): self
    {
        $this->activation_code = $activation_code;
        return $this;
    }

    /**
     * @param string|null $forgotten_password_selector
     * @return self
     */
    public function setForgottenPasswordSelector(?string $forgotten_password_selector = null): self
    {
        $this->forgotten_password_selector = $forgotten_password_selector;
        return $this;
    }

    /**
     * @param string|null $forgotten_password_code
     * @return self
     */
    public function setForgottenPasswordCode(?string $forgotten_password_code = null): self
    {
        $this->forgotten_password_code = $forgotten_password_code;
        return $this;
    }

    /**
     * @param int|null $forgotten_password_time
     * @return self
     */
    public function setForgottenPasswordTime(?int $forgotten_password_time = null): self
    {
        $this->forgotten_password_time = $forgotten_password_time;
        return $this;
    }

    /**
     * @param string|null $remember_selector
     * @return self
     */
    public function setRememberSelector(?string $remember_selector = null): self
    {
        $this->remember_selector = $remember_selector;
        return $this;
    }

    /**
     * @param string|null $remember_code
     * @return self
     */
    public function setRememberCode(?string $remember_code = null): self
    {
        $this->remember_code = $remember_code;
        return $this;
    }

    /**
     * @param int $active
     * @return self
     */
    public function setActive(int $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param string $ip_address
     * @return self
     */
    public function setIpAddress(string $ip_address): self
    {
        $this->ip_address = $ip_address;
        return $this;
    }

    /**
     * @param int|null $last_login
     * @return self
     */
    public function setLastLogin(?int $last_login = null): self
    {
        $this->last_login = $last_login;
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
