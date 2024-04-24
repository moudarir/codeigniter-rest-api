<?php

use Firebase\JWT\JWT;
use Moudarir\CodeigniterApi\Exceptions\DatabaseCreateException;
use Moudarir\CodeigniterApi\Http\Server;
use Moudarir\CodeigniterApi\Models\ApiKey;
use Moudarir\CodeigniterApi\Models\User;

class ApiUsers extends Server
{

    public function index()
    {
        $entity = new User();
        $page = $this->get('page');
        $total = $entity->count();
        $response = [
            'total' => $total,
            'items' => $total === 0 ? [] : $entity->all(['page' => $page, 'limit' => $this->get('limit')]),
        ];

        if ($page !== null) {
            $response['page'] = (int)$page === 0 ? 1 : (int)$page;
        }

        self::getResponse()->ok($response);
    }

    public function show(int $id)
    {
        if ($id <= 0) {
            self::getResponse()->badRequest();
        }

        $item = (new User())->find($id);

        if ($item === null) {
            self::getResponse()->notFound();
        }

        self::getResponse()->ok(['item' => $item]);
    }

    public function create()
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

        try {
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
                ->setPassword($hashedPassword)
                ->create();

            $apikey = (new ApiKey())
                ->setUserId($user->getId())
                ->setKey()
                ->setUsername()
                ->setPassword()
                ->create();

            if ($entity::getDatabase()->trans_status() === false) {
                $entity::getDatabase()->trans_rollback();
            } else {
                $entity::getDatabase()->trans_commit();
            }

            self::getResponse()->ok([
                'message' => "User account created successfully.",
                'data' => [
                    'user_id' => $user->getId(),
                    'api_key' => $apikey->getKey(),
                    'username' => $apikey->getUsername(),
                    'password' => $apikey->getPassword(),
                ]
            ]);
        } catch (DatabaseCreateException $e) {
            $entity::getDatabase()->trans_rollback();
            self::getResponse()->error("Error occurred during account creation.");
        }
    }

    public function update($id)
    {
        self::getResponse()->ok([
            'data' => [
                'info' => $this->getAuthData(),
                'args' => $this->put(),
                'id' => $id,
            ]
        ]);
    }

    public function login()
    {
        $apiConfig = $this->getApiConfig();
        $secret = getenv("JWT_SECRET");
        $secret !== false || $secret = $apiConfig['jwt_secret'];
        $user = (new User())->find($this->getApiKey()['user_id']);
        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'iat' => 1356999524,
            'nbf' => 1357000000,
            'exp' => time() + (60 * 60),
            'user' => [
                'user_id' => $user['id'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
            ]
        ];
        self::getResponse()->ok([
            'data' => [
                'jwt_key' => JWT::encode($payload, $secret, $apiConfig['jwt_algorithm']),
            ]
        ]);
    }

    public function avatar()
    {
        self::getResponse()->ok([
            'data' => [
                'file' => $_FILES,
                'items' => $this->post()
            ]
        ]);
    }
}
