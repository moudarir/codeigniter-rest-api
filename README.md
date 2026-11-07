# CodeIgniter 3 API Rest

[![GitHub release (latest)](https://img.shields.io/github/v/release/moudarir/codeigniter-rest-api)](https://packagist.org/packages/moudarir/codeigniter-rest-api)
[![GitHub license](https://img.shields.io/github/license/moudarir/codeigniter-rest-api)](https://github.com/moudarir/codeigniter-rest-api/blob/master/LICENSE)

A RESTful server implementation for CodeIgniter 3.

## Table of contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Todo](#todo)

## Requirements

- PHP 8.4+
- CodeIgniter 3.1.*.

## Installation

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
    "moudarir/codeigniter-rest-api": "^4.0"
  },
  ...
}
```

And then run:

```bash
composer update
```

## Configuration

> To add on next release.

### Important

Execute the `dumping/queries.sql` file to create the tables needed for the API to work properly.

Tables that will be created are `client`, `client_limit` and `client_log`.

You're now ready to begin using the library 👌.

## Usage

Create the `[your-project]/application/controllers/api/ApiRequests.php` controller:

```php
<?php
defined('BASEPATH') || exit('No direct script access allowed');

use App\Models\Client\Client;
use Moudarir\CIRestApi\ApiController;
use Moudarir\CIRestApi\Exceptions\ApiException;
use Moudarir\CIRestApi\ApiConfig;
use Moudarir\Helpers\Datetime\DatetimeHelper;
use Firebase\JWT\JWT;

/**
 * @property ApiRequests
 */
class ApiRequests extends ApiController
{

    /**
     * @throws ApiException
     */
    public function __construct()
    {
        parent::__construct(ApiConfig::create(
            enableAuthentication: true,
            authenticationClassName: Client::class,
            authenticationJWTSecret: "YOUR_JWT_SECRET",
            authenticationJWTAlgo: "HS256",
            enableApiKey: true,
        ));
    }

    public function token(): void
    {
        $now = DatetimeHelper::now();

        $this->response->ok([
            'data' => JWT::encode(
                [
                    'iss' => base_url(),
                    'iat' => $now->getTimestamp(),
                    'exp' => $now->addMonths()->getTimestamp(),
                    'client_id' => $this->client->getId()
                ],
                $this->apiConfig->authenticationJWTSecret,
                $this->apiConfig->authenticationJWTAlgo
            )
        ]);
    }
}
```

### Authentication methods

The Rest Server can be used with `Basic` or `Bearer` authorization type. However, it can be used without any authorization type (`not secure`).

✨ That's it!

## Todo

- Improve documentation.
