<?php

namespace Moudarir\CodeigniterApi\Http;

class Helpers
{

    /**
     * Return a randomly generated code in following
     * format: XXXXXX-XXXXXX-XXXXXX-XXXXXX => parts = 4 | length = 6
     *
     * @param int $length
     * @param string $type
     * @param int $parts
     * @return string
     */
    public static function generateToken(int $length = 9, string $type = 'alnum', int $parts = 1): string
    {
        switch ($type) {
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            default:
                $pool = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;
        }

        $chars = [];

        for ($i = 0; $i < $parts; ++$i) {
            $chars[] = substr(str_shuffle($pool), 0, $length);
        }

        return implode('-', $chars);
    }

    /**
     * @param string $string
     * @param string $separator
     * @return string
     */
    public static function stringToCamelcase(string $string, string $separator = '_'): string
    {
        if (empty($string)) {
            return '';
        }

        return lcfirst(str_replace(' ', '', ucwords(str_replace($separator, ' ', $string))));
    }
}
