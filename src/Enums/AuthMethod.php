<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Enums;

enum AuthMethod: string
{

    case BASIC = 'basic';
    case BEARER = 'bearer';
}
