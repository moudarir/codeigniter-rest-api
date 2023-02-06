<?php

use Firebase\JWT\JWT;
use Moudarir\CodeigniterApi\Models\Api\ApiKey;

/**
 * @property UsersTest
 */
class UsersTest extends CoreTest
{

    /**
     * UsersTest constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function resetKey(): void
    {
        dump((new ApiKey())->reset([1]));
    }

    public function jwt()
    {
        $secret = getenv("JWT_SECRET");
        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'iat' => 1356999524,
            'nbf' => 1357000000,
            'exp' => time() + (60 * 60),
            'user' => [
                'id' => 1,
                'firstname' => 'John',
                'lastname' => 'DOE',
                'email' => 'john@doe.com',
                'role' => 'super'
            ]
        ];
        dump(JWT::encode($payload, $secret, 'HS256'));
    }
}
