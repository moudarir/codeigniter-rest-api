<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Enums;

enum HttpMethod: string
{

    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
}
