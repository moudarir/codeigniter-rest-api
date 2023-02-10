# Codeigniter 3 API Rest

[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/moudarir/codeigniter-rest-api/blob/master/LICENSE)

A RESTful server implementation for Codeigniter 3 based on [CodeIgniter RestServer](https://github.com/chriskacerguis/codeigniter-restserver "CodeIgniter RestServer")

## Table of contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Implementation](#implementation)
4. [Usage](#usage)
5. [Postman collection](#postman-collection)

### Requirements

- PHP:  `>=7.4`
- Codeigniter: `^3.1.0`
- Composer

### Installation

> **N.B:** The current version `1.0.*` requires php 7.4 or higher ([php supported versions](http://php.net/supported-versions.php))

This library uses [Composer](https://getcomposer.org/)  to by installed.

Run this command (recommended) in the same path as your `composer.json` file:

```bash
composer require moudarir/codeigniter-rest-api
```

Or, In your `composer.json` file, add the following code in `require` section: 

```json
    {
        "require": {
          "moudarir/codeigniter-rest-api": "^1.0"
        }
    }
```

And then run:

```bash
composer install
```

### Implementation

**Language/Translation**

You can find the file associated with your language in the `application/languages/` folder. Based on the `$config['language']` setting in your `application/config/config.php` configuration file.

*Supported languages:*

- English
- French

**Some changes to perform**

The first thing to do is copy the `rest-api.php` configuration file into the `config` folder of your codeigniter application

> **Tip:** If you find that the default configuration works for you, then copying the configuration file is optional.

Make sure that the `enable_hooks`, `subclass_prefix` and `composer_autoload`  keys in `application/config/config.php` file are set as following:

```php
$config['enable_hooks'] = true;
$config['subclass_prefix'] = 'Core';
$config['composer_autoload'] = true; // Or the path to 'autoload.php' file. Ex: APPPATH.'vendor/autoload.php'
```

Next, append the following code to your `application/config/hooks.php` file:

```php
$hook['pre_system'][] = [
    'class' => 'InitAppCore',
    'function' => 'initialize',
    'filename' => 'InitAppCore.php',
    'filepath' => 'hooks'
];
```

Then, create a new file called `InitAppCore.php` in `application/hooks/` folder and put the following code:

```php
<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * @property InitAppCore
 */
class InitAppCore
{

    /**
     * @return void
     */
    public function initialize(): void
    {
        spl_autoload_register([__CLASS__, 'customCores']);
    }

    /**
     * @param string $class_name
     */
    public function customCores(string $class_name)
    {
        if (strpos($class_name, 'CI_') !== 0) {
            $class_file = $class_name.'.php';
            if (is_readable(APPPATH.'core'.DIRECTORY_SEPARATOR.$class_file)) {
                require_once(APPPATH.'core'.DIRECTORY_SEPARATOR.$class_file);
            }
        }
    }
}

```

And now, we can create a custom core class to use in our Controllers.

Referring to `subclass_prefix` param, we should prefix the class name with `Core`, Ex: `/application/core/CoreApi.php`.

```php
<?php
defined('BASEPATH') || exit('No direct script access allowed');

use Moudarir\CodeigniterApi\Http\Server;

class CoreApi extends Server
{

    /**
     * CoreServer constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
}

```

**IMPORTANT**

Execute the `dumping/queries.sql` file to create the tables needed for the API to work properly.

Tables that will be created are `users`, `roles`, `users_roles`, `api_keys` and `api_key_logs`.

You're now ready to begin using the library 👌.

**A word about controller methods and requests**

Basicly, There is a relationship between `controller method` and `request method`.

If you send a `GET` request, the controller method **MUST BE** named as `indexGet`

Request examples:
```bash
GET /users HTTP/1.1 => indexGet
GET /users/1 HTTP/1.1 => indexGet
POST /users HTTP/1.1 => indexPost
POST /users/login HTTP/1.1 => loginPost
```

### Usage

Adding some routes for the next example in `application/config/routes.php` file.

```php
$route['users'] = [
    "get" => "users",
    "post" => "users",
    "head" => "users",
    "options" => "users"
];
$route['users(\.)([a-zA-Z0-9_-]+)(.*)'] = [
    "get" => "users/format/$2$3",
    "post" => "users/format/$2$3"
];
$route['users/([0-9]+)'] = [
    "get" => "users/index/id/$1",
    "put" => "users/index/id/$1"
];
$route['users/([0-9]+)(\.)([a-zA-Z0-9_-]+)(.*)'] = [
    "get" => "users/index/id/$1/format/$3$4",
    "put" => "users/index/id/$1/format/$3$4"
];
$route['users/login'] = [
    "post" => "users/login"
];
$route['users/login(\.)([a-zA-Z0-9_-]+)(.*)'] = [
    "post" => "users/login/format/$2$3"
];
```

And now, we can create our `application/controllers/Users.php` controller:

```php

<?php
defined('BASEPATH') || exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Moudarir\CodeigniterApi\Http\Helpers;
use Moudarir\CodeigniterApi\Models\Api\ApiKey;
use Moudarir\CodeigniterApi\Models\Users\User;
use Moudarir\CodeigniterApi\Models\Users\UserRole;

/**
 * @property Users
 */
class Users extends CoreApi
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
        $response = [
            'total' => $total,
            'items' => $total === 0 ? [] : $entity->normalizeAll($entity->all($options)),
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

```

**Authentication methods**

The Rest Server can be used/combined with `basic` and `Bearer` authorization type. However, it's can be used without any authorization type (`not secure`)

### Postman collection

#### Import the collection

Downloaded our [Postman collection](https://github.com/moudarir/codeigniter-rest-api/blob/master/Codeigniter%20API%20REST.postman_collection.json "Codeigniter REST API Postman Collection"), and import it into Postman.

#### Import the environment

We have also provided a [Postman Environment](https://github.com/moudarir/codeigniter-rest-api/blob/master/Codeigniter%20API%20REST.postman_environment.json "Codeigniter REST API Postman Environment") that you need to import as well.

> **Note:** To understand what Postman environments are, please check [this link](https://learning.postman.com/docs/sending-requests/managing-environments/ "Managing environments in Postman").

**Edit the environment variables**

Update the `endpoint` variable to point to your Rest server. Ex: (https//myapi.com/) with trailing slash.

✨ That's it!

You can now use the Postman collection to test available requests.

> **Note:** In the Postman collection, the order of execution of the requests must be respected.
