<?php

namespace Moudarir\CodeigniterApi\Helpers;

use function log_message;

class CommonHelper
{

    public static function charsFromPosition(string $string, int $start = 0, ?int $length = null): ?string
    {
        $char = \substr($string, $start, $length);

        return $char !== '' ? $char : null;
    }

    public static function firstLetter(string $word, ?string $format = null): ?string
    {
        $letter = self::charsFromPosition($word, 0, 1);
        switch ($format) {
            case 'upper':
            default:
                $letter = \strtoupper($letter);
                break;
            case 'lower':
                $letter = \strtolower($letter);
                break;
        }

        return $letter;
    }

    public static function firstLetters(string $string, string $separator = '.', ?string $format = null): string
    {
        $keywords = \explode(' ', \trim($string));
        $letters = [];
        foreach ($keywords as $word) {
            if (\trim($word) !== '') {
                $letters[] = self::firstLetter($word, $format);
            }
        }

        return \implode($separator, $letters);
    }

    public static function camelcase(string $camelcase, string $separator = ' '): string
    {
        $regx = '/
        (?<=[a-z])      # Position is after a lowercase,
        (?=[A-Z])       # and before an uppercase letter.
        | (?<=[A-Z])    # Or g2of2; Position is after uppercase,
        (?=[A-Z][a-z])  # and before upper-then-lower case.
        /x';
        $a = \preg_split($regx, $camelcase);

        return \join($separator, $a);
    }

    public static function stringToCamelcase(string $string, string $separator = '_'): string
    {
        if (empty($string)) {
            return '';
        }

        return \lcfirst(\str_replace(' ', '', \ucwords(\str_replace($separator, ' ', $string))));
    }

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
                $pool = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;
        }

        $chars = [];

        for ($i = 0; $i < $parts; ++$i) {
            $chars[] = \substr(\str_shuffle($pool), 0, $length);
        }

        return \implode('-', $chars);
    }

    public static function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && \strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function logException($exception)
    {
        log_message('error', $exception->getMessage().'. File: '.$exception->getFile().', in line '.$exception->getLine().'.');
    }

    public static function &apiConfig(): array
    {
        static $config;

        if (empty($config)) {
            if (\file_exists(APPPATH . 'config/rest-api-server.php')) {
                include(APPPATH . 'config/rest-api-server.php');
            } elseif (\file_exists(APPPATH . 'config/' . ENVIRONMENT . '/rest-api-server.php')) {
                include(APPPATH . 'config/' . ENVIRONMENT . '/rest-api-server.php');
            } else {
                if (\file_exists(__DIR__.'/rest-api-server.php')) {
                    include __DIR__.'/rest-api-server.php';
                }
            }
        }

        return $config;
    }
}
