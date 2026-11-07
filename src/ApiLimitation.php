<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi;

use Moudarir\CIRestApi\Enums\LimitType;
use Moudarir\CIRestApi\Exceptions\ApiException;
use Moudarir\CIRestApi\Helpers\Common;
use Moudarir\CIRestApi\Interfaces\ApiClientInterface;
use Moudarir\Helpers\Datetime\DatetimeHelper;
use function get_instance;

final readonly class ApiLimitation
{

    public function __construct(private ApiClientInterface $client, private ApiConfig $apiConfig)
    {}

    /**
     * @throws ApiException
     */
    public function check(string $classMethod, string $method): void
    {
        $requestLimits = $this->client->getLimits() ?: $this->apiConfig->limitsNumRequests;
        $request = match ($this->apiConfig->limitType) {
            LimitType::IP_ADDRESS => 'ip:'.Common::ipAddress(),
            LimitType::METHOD_NAME => 'method:'.$classMethod,
            default => 'uri:'.get_instance()->uri->ruri_string().':'.$method,
        };

        $entity = new $this->apiConfig->limitsClassName();
        $entity::getDatabase()->trans_begin();

        if (($limitation = $entity->find($this->client->getId(), $request)) === null) {
            $result = $entity->insert([
                'client_id' => $this->client->getId(),
                'request' => $request
            ]);

            if ($result === false) {
                throw ApiException::generic("Internal error on limitation save.");
            }

            $entity::getDatabase()->trans_commit();
            return;
        }

        $timestamp = DatetimeHelper::now()->getTimestamp();
        $limitsTimeout = (int)$this->client->getResetLimitsAfter() > 0
            ? ((int)$this->client->getResetLimitsAfter() * $this->apiConfig->limitsTimeout)
            : ($this->apiConfig->limitsTimeout * 60); // 1 hour

        if ($limitation->getStartedAt() < ($timestamp - $limitsTimeout)) {
            $limitation->setCounter(0)->setStartedAt($timestamp)->setUpdatedAt();
        }

        // The limit has been exceeded
        if ($limitation->getCounter() >= $requestLimits) {
            $entity::getDatabase()->trans_rollback();
            throw ApiException::limitRequestsExceeded();
        }

        if ($limitation->setCounter($limitation->getCounter() + 1)->update() === false) {
            $entity::getDatabase()->trans_rollback();
            throw ApiException::internalServerError();
        }

        $entity::getDatabase()->trans_commit();
    }
}
