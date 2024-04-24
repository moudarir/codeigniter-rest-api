# Codeigniter 3 API Rest
[![GitHub release (latest)](https://img.shields.io/github/v/release/moudarir/codeigniter-rest-api)](https://packagist.org/packages/moudarir/codeigniter-rest-api)
[![GitHub license](https://img.shields.io/github/license/moudarir/codeigniter-rest-api)](https://github.com/moudarir/codeigniter-rest-api/blob/master/LICENSE)

A RESTful server implementation for Codeigniter 3 based on [CodeIgniter RestServer](https://github.com/chriskacerguis/codeigniter-restserver "CodeIgniter RestServer")

> **Break changes** made in version `3.0`. Full code refactoring and new route system implemented inspired from [Luthier CI](https://github.com/ingeniasoftware/luthier-ci "Luthier CI").

## Table of contents
1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Implementation](#implementation)
4. [Usage](#usage)
5. [Postman collection](#postman-collection)
6. [Todo](#todo)

## Requirements
- PHP: `7.4` to `8.2` tested.
- Codeigniter: `^3.1.13`.
- Composer

## Installation
> The current version `3.0.*` requires php 7.4 or higher ([php supported versions](http://php.net/supported-versions.php))

This library uses [Composer](https://getcomposer.org/) to be installed.

Run this command line (recommended) in the same path as your `composer.json` file:

```bash
composer require moudarir/codeigniter-rest-api
```

Or, In your `composer.json` file, add the following code in `require` section: 

```php
{
  ...
  "require": {
    ...
    "moudarir/codeigniter-rest-api": "^3.0"
  },
  ...
}
```

And then run:

```bash
composer install
```

## Implementation

### Language / Translation
You can find the file associated with your language in the `application/language/` folder. Based on the `$config['language']` setting in your `[your-project]/application/config/config.php` configuration file.

### Supported languages
- English
- French
- Arabic

### Files & Configuration
The first thing to do is copying all required files in your CI project:

- `application/config/rest-api-server.php` => `[your-project]/application/config/rest-api-server.php` 
- `application/controllers/DefaultController.php` => `[your-project]/application/controllers/DefaultController.php`
- `application/language/*/rest-api-server_lang.php` => `[your-project]/application/language/*/rest-api-server_lang.php`
- `application/routes/*` => `[your-project]/application/routes/*`

> **DO NOT** change the `[your-project]/application/config/rest-api-server.php` and `[your-project]/application/language/*/rest-api-server_lang.php` filenames.

Make sure that the `enable_hooks` and `composer_autoload` keys in `[your-project]/application/config/config.php` file are set as following:

```php
$config['enable_hooks'] = true;
$config['composer_autoload'] = true; // Or the path to 'autoload.php' file. Ex: APPPATH.'vendor/autoload.php'
```

Next, set the following code in `[your-project]/application/config/hooks.php` file:

```php
$hook = \Moudarir\CodeigniterApi\Routes\Hook::initialize();
```

and in `[your-project]/application/config/routes.php` file:
```php
$route = \Moudarir\CodeigniterApi\Routes\Router::getRoutes();
```

### Important
Execute the `dumping/queries.sql` file to create the tables needed for the API to work properly.

Tables that will be created are `users`, `api_keys`, `api_key_limits` and `api_key_logs`.

You're now ready to begin using the library 👌.

### About Routes
The implementation of old routes is deprecated. The routes are now simplified for best use. See [Usage](#usage).

## Usage
Adding some routes for the next example in `[your-project]/application/routes/api.php` file (if not exists).

```php
\Moudarir\CodeigniterApi\Routes\Router::group('users', ['namespace' => 'api'], function () {
    \Moudarir\CodeigniterApi\Routes\Router::get('', 'apiUsers@index');
    \Moudarir\CodeigniterApi\Routes\Router::post('', 'apiUsers@create');
    \Moudarir\CodeigniterApi\Routes\Router::post('login', 'apiUsers@login');
    \Moudarir\CodeigniterApi\Routes\Router::put('{id}', 'apiUsers@update');
    \Moudarir\CodeigniterApi\Routes\Router::get('{id}', 'apiUsers@show');
});

// This will generate route array like this:
/**
$route = [
  "users" => [
    "GET" => "api/apiUsers/index",
    "POST" => "api/apiUsers/create",
  ],
  "users/login" => [
    "POST" => "api/apiUsers/login"
  ],
  "users/([0-9]+)" => [
    "PUT" => "api/apiUsers/update/$1"
    "GET" => "api/apiUsers/show/$1"
  ],
  "default_controller" => "welcome", // Can be changed in '[your-project]/application/config/rest-api-server.php' file.
  "translate_uri_dashes" => false, // Can be changed in '[your-project]/application/config/rest-api-server.php' file.
  "404_override" => "pageNotFound/index", // Can be changed in '[your-project]/application/config/rest-api-server.php' file.
]
**/
```

And now, we can create our `[your-project]/application/controllers/api/ApiUsers.php` controller:

```php
<?php
defined('BASEPATH') || exit('No direct script access allowed');

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
                'memory_cost' => 1 << 12,
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
}
```

### Authentication methods
The Rest Server can be used with `Basic` or `Bearer` authorization type. However, it can be used without any authorization type (`not secure`).

The request limit currently only works in the `Basic` authorization type.

## Postman collection

### Import the collection
Downloaded our [Postman collection](https://github.com/moudarir/codeigniter-rest-api/blob/master/Codeigniter%20API%20REST.postman_collection.json "Codeigniter REST API Postman Collection"), and import it into Postman.

### Import the environment
We have also provided a [Postman Environment](https://github.com/moudarir/codeigniter-rest-api/blob/master/Codeigniter%20API%20REST.postman_environment.json "Codeigniter REST API Postman Environment") that you need to import as well.

> To understand what Postman environments are, please check [this link](https://learning.postman.com/docs/sending-requests/managing-environments/ "Managing environments in Postman").

### Edit the environment variables
Update the `endpoint` variable to point to your Rest server. Ex: (https//myapi.com/) with trailing slash.

✨ That's it!

You can now use the Postman collection to test available requests.

> In the Postman collection, the order of execution of the requests must be respected.

## Todo
- Write routing HOW TO USE documentation.
- Improve documentation.
