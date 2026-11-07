<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Enums\StatusCode;

final readonly class ApiResponse
{

    public function __construct(private ApiConfig $apiConfig, private ?ApiLogger $logger = null)
    {}

    public function ok(array $data = [], ?StatusCode $statusCode = null): void
    {
        $result = array_merge(['error' => false], $data);

        if (array_key_exists('count', $result)) {
            $result['total'] = $result['count'];
            unset($result['count']);
        }

        if (array_key_exists('limit', $result)) {
            $result['per_page'] = $result['limit'];
            unset($result['limit']);
        }

        if (array_key_exists('items', $result)) {
            $result['data'] = $result['items'];
            unset($result['items']);
        } elseif (array_key_exists('item', $result)) {
            $result['data'] = $result['item'];
            unset($result['item']);
        }

        $this->response($result, $statusCode ?: StatusCode::OK);
    }

    /**
     * Error (with 200 response code)
     */
    public function error(array|string $message, ?StatusCode $statusCode = null): void
    {
        $data = ['error' => true];

        if (is_array($message)) {
            $allKeysAreStrings = array_all($message, fn ($value, $key) => is_string($key));

            if ($allKeysAreStrings) {
                $data = array_merge($data, $message);
            } else {
                $data['reasons'] = $message;
            }
        } else {
            $data['message'] = $message;
        }

        $this->response($data, $statusCode ?: StatusCode::OK);
    }

    /**
     * For form validation
     */
    public function validationError(array $errors): void
    {
        $this->error($errors, StatusCode::UNPROCESSABLE_ENTITY);
    }

    public function created(array $data = []): void
    {
        $this->ok($data, StatusCode::CREATED);
    }

    public function modified(array $data = []): void
    {
        $this->ok($data);
    }

    public function notModified(array $data = []): void
    {
        $this->ok($data, StatusCode::NOT_MODIFIED);
    }

    public function badRequest(?string $message = null): void
    {
        $this->response(
            !empty($message) ? ['error' => true, 'message' => $message] : [],
            StatusCode::BAD_REQUEST
        );
    }

    public function unauthorized(?string $message = null): void
    {
        $this->response([
            'error' => true,
            'message' => $message ?: "Non autorisée.",
        ], StatusCode::UNAUTHORIZED);
    }

    public function forbidden(?string $message = null): void
    {
        $this->response([
            'error' => true,
            'message' => $message ?: "Accès interdit.",
        ], StatusCode::FORBIDDEN);
    }

    public function notFound(?string $message = null): void
    {
        $this->response([
            'error' => true,
            'message' => $message ?: "Ressource introuvable.",
        ], StatusCode::NOT_FOUND);
    }

    public function methodNotAllowed(?string $message = null): void
    {
        $this->response([
            'error' => true,
            'message' => $message ?: "Méthode introuvable.",
        ], StatusCode::METHOD_NOT_ALLOWED);
    }

    public function internalError(): void
    {
        $this->response([
            'error' => true,
            'message' => "Erreur interne du serveur.",
        ], StatusCode::INTERNAL_SERVER_ERROR);
    }

    public function response($data = null, StatusCode|int|null $statusCode = null): void
    {
        if (is_int($statusCode)) {
            $statusCode = StatusCode::tryFrom($statusCode);
        }

        $statusCode = $statusCode ?: StatusCode::NOT_FOUND;

        if ($this->apiConfig->enableLogging === true) {
            $this->logger?->push(['response_code' => $statusCode->value]);
        }

        self::headerContentType();
        self::headerStatus($statusCode);

        echo !empty($data)
            ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP)
            : '';
        exit();
    }

    private static function headerContentType(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private static function headerStatus(StatusCode $statusCode): void
    {
        $code = $statusCode->value;
        $text = $statusCode->text();

        if (str_starts_with(PHP_SAPI, 'cgi')) {
            header('Status: ' . $code . ' ' . $text);
            return;
        }

        if (
            isset($_SERVER['SERVER_PROTOCOL']) &&
            in_array($_SERVER['SERVER_PROTOCOL'], ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/3'], true)
        ) {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        } else {
            $protocol = 'HTTP/1.1';
        }

        header($protocol.' '.$code.' '.$text, true, $code);
    }
}
