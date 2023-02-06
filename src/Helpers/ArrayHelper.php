<?php

namespace Moudarir\CodeigniterApi\Helpers;

class ArrayHelper
{

    /**
     * @param array|string|null $with
     * @return array
     */
    public static function formatApiWith($with = null): array
    {
        $_final = [
            'api_format' => true
        ];
        if ($with === null) {
            return $_final;
        }

        if (!is_array($with)) {
            $with = [$with];
        }

        collect($with)->each(function ($value, $key) use (&$_final) {
            if (!is_string($key)) {
                $_final[$value] = true;
            } else {
                $_final[$key] = $value;
            }
        });

        return $_final;
    }
}
