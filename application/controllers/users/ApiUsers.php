<?php

use Firebase\JWT\JWT;
use Moudarir\CodeigniterApi\http\Helpers;
use Moudarir\CodeigniterApi\Models\Api\ApiKey;
use Moudarir\CodeigniterApi\Models\Users\User;
use Moudarir\CodeigniterApi\Models\Users\UserRole;

/**
 * @property ApiUsers
 */
class ApiUsers extends CoreServer
{
    /**
     * Based from 'roles' table
     * @var array
     */
    private array $roles = [
        'moderator' => 1,
        'admin' => 2,
        'super' => 3,
        'member' => 4,
    ];

    /**
     * ApiUsers constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see ApiUsers::indexGet()
     */
    public function indexGet()
    {
        $id = $this->get('id');
        $with = $this->get('with');
        $options = Helpers::formatApiWith($with);
        $entity = new User();

        if ($id !== null) {
            if ((int)$id <= 0) {
                self::getResponse()->badRequest();
            }

            $item = $entity->find($id, $options);

            if ($item === null) {
                self::getResponse()->notFound();
            }

            self::getResponse()->ok(['item' => $item->normalize()]);
        }

        $total = $entity->count($options);
        $options['page'] = $this->get('page');
        $options['limit'] = $this->get('limit');
        $items = $entity->collect($options)->toArray();
        $response = [
            'total' => $total,
            'items' => $total === 0 ? [] : $entity->normalizeAll($items),
        ];

        if ($options['page'] !== null) {
            $response['page'] = (int)$options['page'] === 0 ? 1 : (int)$options['page'];
        }

        self::getResponse()->ok($response);
    }

    /**
     * @see ApiUsers::indexPost()
     */
    public function indexPost()
    {
        $post = $this->post();
        $errors = [];

        if (array_key_exists('email', $post)) {
            $email = $this->post('email');

            if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = "This field is not a valid email address.";
            }
        } else {
            $errors['email'] = "This field is required.";
        }

        if (!empty($errors)) {
            self::getResponse()->error($errors);
        }

        $entity = new User();
        $hashedPassword = password_hash($post['password'], PASSWORD_ARGON2I, [
            'memory_cost' => 1 << 12, // 4MB
            'time_cost' => 2,
            'threads' => 2
        ]);
        $entity::getDatabase()->trans_start();
        $user = $entity
            ->setFirstname($post['firstname'])
            ->setLastname($post['lastname'])
            ->setEmail($post['email'])
            ->setPassword($hashedPassword);
        $user_id = $user->create();

        if ($user_id === null) {
            $entity::getDatabase()->trans_rollback();
            self::getResponse()->error("Error occurred during account creation.");
        }

        $urEntity = new UserRole();
        $role_id = $this->roles[$post['role']];
        $userRole = $urEntity->setUserId($user_id)->setRoleId($role_id);
        $user_role_id = $userRole->create();

        if ($user_role_id === null) {
            $entity::getDatabase()->trans_rollback();
            self::getResponse()->error("Error occurred during account creation.");
        }

        $akEntity = new ApiKey();
        $apiKey = $akEntity
            ->setUserId($user_id)
            ->setKey()
            ->setUsername()
            ->setPassword()
            ->setIpAddresses();
        $api_key_id = $apiKey->create();

        if ($api_key_id === null) {
            $entity::getDatabase()->trans_rollback();
            self::getResponse()->error("Error occurred during account creation.");
        }

        if ($entity::getDatabase()->trans_status() === false) {
            $entity::getDatabase()->trans_rollback();
        } else {
            $entity::getDatabase()->trans_commit();
        }

        self::getResponse()->ok([
            'message' => "User account created successfully.",
            'data' => [
                'user_id' => $apiKey->getUserId(),
                'api_key' => $apiKey->getKey(),
                'username' => $apiKey->getUsername(),
                'password' => $apiKey->getPassword(),
            ]
        ]);
    }

    /**
     * @see ApiUsers::indexPut()
     */
    public function indexPut()
    {
        self::getResponse()->ok([
            'data' => [
                'info' => $this->getAuthData(),
                'args' => $this->put()
            ]
        ]);
    }

    /**
     * @see ApiUsers::loginPost()
     */
    public function loginPost()
    {
        $secret = getenv("JWT_SECRET");
        $user = (new User())->find($this->getApiKey()->getUserId(), ['role' => true]);
        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'iat' => 1356999524,
            'nbf' => 1357000000,
            'exp' => time() + (60 * 60),
            'user' => [
                'user_id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'role' => $user->getUserRole()->getName(),
            ]
        ];
        self::getResponse()->ok([
            'data' => [
                'jwt_key' => JWT::encode($payload, $secret, 'HS256'),
            ]
        ]);
    }
}
