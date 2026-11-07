<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Enums;

enum StatusCode: int
{

    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;
    case PARTIAL_CONTENT = 206;
    case MOVED_PERMANENTLY = 301;
    case NOT_MODIFIED = 304;
    case TEMPORARY_REDIRECT = 307;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case NOT_ACCEPTABLE = 406;
    case UNPROCESSABLE_ENTITY = 422;
    case TOO_MANY_REQUESTS = 429;
    case INTERNAL_SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;
    case HTTP_VERSION_NOT_SUPPORTED = 505;

    public function text(): string
    {
        return match ($this) {
            self::OK => "OK",
            self::CREATED => "Created",
            self::NO_CONTENT => "No Content",
            self::PARTIAL_CONTENT => "Partial Content",
            self::MOVED_PERMANENTLY => "Moved Permanently",
            self::NOT_MODIFIED => "Not Modified",
            self::TEMPORARY_REDIRECT => "Temporary Redirect",
            self::BAD_REQUEST => "Bad Request",
            self::UNAUTHORIZED => "Unauthorized",
            self::FORBIDDEN => "Forbidden",
            self::NOT_FOUND => "Not Found",
            self::METHOD_NOT_ALLOWED => "Method Not Allowed",
            self::NOT_ACCEPTABLE => "Not Acceptable",
            self::UNPROCESSABLE_ENTITY => "Unprocessable Entity",
            self::TOO_MANY_REQUESTS => "Too Many Requests",
            self::INTERNAL_SERVER_ERROR => "Internal Server Error",
            self::SERVICE_UNAVAILABLE => "Service Unavailable",
            self::HTTP_VERSION_NOT_SUPPORTED => "HTTP Version Not Supported",
        };
    }
}
