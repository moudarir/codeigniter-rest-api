<?php

namespace Moudarir\CodeigniterApi\Helpers;

class StringHelper
{

    /**
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string|null
     */
    public static function charsFromPosition(string $string, int $start = 0, ?int $length = null): ?string
    {
        $char = substr($string, $start, $length);

        return $char !== '' ? $char : null;
    }

    /**
     * @param string $word
     * @param string|null $format
     * @return string|null
     */
    public static function firstLetter(string $word, ?string $format = null): ?string
    {
        $letter = self::charsFromPosition($word, 0, 1);
        switch ($format) {
            case 'upper':
            default:
                $letter = strtoupper($letter);
                break;
            case 'lower':
                $letter = strtolower($letter);
                break;
        }

        return $letter;
    }

    /**
     * @param string $string
     * @param string $separator
     * @param string|null $format
     * @return string
     */
    public static function firstLetters(string $string, string $separator = '.', ?string $format = null): string
    {
        $keywords = explode(' ', trim($string));
        $letters = [];
        foreach ($keywords as $word) {
            if (trim($word) !== '') {
                $letters[] = self::firstLetter($word, $format);
            }
        }

        return implode($separator, $letters);
    }
}
