<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Interfaces;

interface ApiLoggerInterface
{

    public function save(array $data): bool;
}
