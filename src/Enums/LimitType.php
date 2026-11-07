<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Enums;

enum LimitType: string
{

    case ROUTED_URL = 'ROUTED_URL';
    case IP_ADDRESS = 'IP_ADDRESS';
    case METHOD_NAME = 'METHOD_NAME';
}
