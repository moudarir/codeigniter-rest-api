<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Interfaces;

interface ApiLimitationInterface
{

    public function find(int $clientId, string $request): ?self;

    public function insert(array $data): bool;
}
