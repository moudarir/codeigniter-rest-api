<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Exceptions;

use Moudarir\CIRestApi\Enums\StatusCode;
use Exception;
use Throwable;

class ApiException extends Exception
{

    public static function generic(string $message, ?Throwable $previous = null): static
    {
        return new static($message, StatusCode::INTERNAL_SERVER_ERROR->value, $previous);
    }

    public static function blacklistedIpAddress(): static
    {
        return new static("Adresse IP refusée.", StatusCode::UNAUTHORIZED->value);
    }

    public static function ipAddressNotAllowed(string $ip): static
    {
        return new static("Adresse IP non autorisée `$ip`.", StatusCode::UNAUTHORIZED->value);
    }

    public static function httpMethodNotAllowed(string $method): static
    {
        return new static(sprintf("La méthode HTTP `%s` n'est pas autorisé.", $method), StatusCode::METHOD_NOT_ALLOWED->value);
    }

    public static function httpMethodNotRecognized(string $method): static
    {
        return new static(sprintf("La méthode HTTP `%s` n'est pas reconnue.", $method), StatusCode::METHOD_NOT_ALLOWED->value);
    }

    public static function invalidHttpMethods(): static
    {
        return new static("Invalid provided methods.", StatusCode::METHOD_NOT_ALLOWED->value);
    }

    public static function authenticationClassNameNotExists(): static
    {
        return new static(
            "The authentication class name is undefined or not exists.",
            StatusCode::INTERNAL_SERVER_ERROR->value
        );
    }

    public static function authenticationClassNameNotImplement(string $className): static
    {
        return new static(
            "The class name `$className` must implements the `HttpClientInterface` interface.",
            StatusCode::INTERNAL_SERVER_ERROR->value
        );
    }

    public static function loggerClassNameNotExists(): static
    {
        return new static(
            "The logger class name is undefined or not exists.",
            StatusCode::INTERNAL_SERVER_ERROR->value
        );
    }

    public static function loggerClassNameNotImplement(string $className): static
    {
        return new static(
            "The class name `$className` must implements the `HttpLoggerInterface` interface.",
            StatusCode::INTERNAL_SERVER_ERROR->value
        );
    }

    public static function limitationClassNameNotExists(): static
    {
        return new static(
            "The limitation class name is undefined or not exists.",
            StatusCode::INTERNAL_SERVER_ERROR->value
        );
    }

    public static function limitationClassNameNotImplement(string $className): static
    {
        return new static(
            "The class name `$className` must implements the `HttpLimitationInterface` interface.",
            StatusCode::INTERNAL_SERVER_ERROR->value
        );
    }

    public static function missingApiKey(): static
    {
        return new static("Clé API manquante.", StatusCode::UNAUTHORIZED->value);
    }

    public static function authenticationFailed(?Throwable $previous = null): static
    {
        return new static(
            "L'authentification a échoué.",
            StatusCode::UNAUTHORIZED->value,
            $previous
        );
    }

    public static function internalServerError(?Throwable $previous = null): static
    {
        return new static(
            "Erreur interne du serveur.",
            StatusCode::INTERNAL_SERVER_ERROR->value,
            $previous
        );
    }

    public static function invalidTokenSignature(?Throwable $previous = null): static
    {
        return new static(
            "La vérification de la signature du jeton a échoué.",
            StatusCode::UNAUTHORIZED->value,
            $previous
        );
    }

    public static function beforeValidError(string $error): static
    {
        return new static($error, StatusCode::UNAUTHORIZED->value);
    }

    public static function expiredTokenKey(?Throwable $previous = null): static
    {
        return new static(
            "La clé du jeton a expiré.",
            StatusCode::UNAUTHORIZED->value,
            $previous
        );
    }

    public static function limitRequestsExceeded(): static
    {
        return new static("La limite de requêtes est dépassée pour cette ressource.", StatusCode::TOO_MANY_REQUESTS->value);
    }

    public static function bodyBadFormatted(?Throwable $previous = null): static
    {
        return new static(
            "Le corps de la requête est mal formaté.",
            StatusCode::BAD_REQUEST->value,
            $previous
        );
    }
}
