<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Interfaces;

interface ApiClientInterface
{

    public function basicVerification(array $params): ?self;

    public function bearerVerification(int $id): ?self;
}
