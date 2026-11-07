<?php

declare(strict_types=1);

namespace Moudarir\CIRestApi\Helpers;

use Moudarir\Helpers\IsHelper;
use function get_instance;

final class Common
{

    private static ?string $ipAddress = null;

    public static function ipAddress(): string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        return self::$ipAddress ??= (IsHelper::validIP($ipAddress) ? $ipAddress : '0.0.0.0');
    }

    public static function fetchFromArray(array &$array, int|string|null $index = null, bool $xssClean = false): mixed
    {
        // If $index is NULL, it means that the whole $array is requested
        if ($index === null) {
            $index = array_keys($array);
        }

        // allow fetching multiple keys at once
        if (is_array($index)) {
            $output = [];
            foreach ($index as $key) {
                $output[$key] = self::fetchFromArray($array, $key, $xssClean);
            }

            return $output;
        }

        if (array_key_exists($index, $array)) {
            $value = $array[$index];
        } elseif (($count = preg_match_all('/^[^\[]+|\[[^]]*]/', $index, $matches)) > 1) {
            // Does the index contain array notation
            $value = $array;
            for ($i = 0; $i < $count; $i++) {
                $key = trim($matches[0][$i], '[]');
                // Empty notation will return the value as array
                if ($key === '') {
                    break;
                }

                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }

        return $xssClean === true ? self::xssClean($value) : $value;
    }

    public static function xssClean(mixed $value): array|bool|string|int|float|null
    {
        return get_instance()->security->xss_clean($value);
    }
}
