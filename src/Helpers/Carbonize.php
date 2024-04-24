<?php

namespace Moudarir\CodeigniterApi\Helpers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use function config_item;

class Carbonize extends Carbon
{

    /**
     * @var \DateTimeInterface|string|mixed|null
     */
    private $timezone;

    private string $language_to_locale;

    const LANGUAGES = [
        'arabic' => 'ar_SA',
        'english' => 'en',
        'french' => 'fr_FR',
    ];

    public function __construct($time = null, $timezone = null)
    {
        try {
            $this->timezone = $timezone ?: config_item('time_reference');
            $this->language_to_locale = self::LANGUAGES[config_item('language')] ?? 'en';
            parent::__construct($time ?: 'now', $this->timezone);
            $this->locale($this->language_to_locale);
        } catch (InvalidFormatException $exception) {
            CommonHelper::logException($exception);
        }
    }

    /**
     * @param int $timestamp
     * @return string|Carbonize|null
     */
    public function getFromTimestamp(int $timestamp)
    {
        try {
            return $this->createFromTimestamp($timestamp, $this->timezone)->locale($this->language_to_locale);
        } catch (InvalidFormatException $exception) {
            CommonHelper::logException($exception);
            return null;
        }
    }

    /**
     * @param string $time
     * @return string|Carbonize|null
     */
    public function getFromFormat(string $time)
    {
        try {
            return (static::parse($time, $this->timezone))->locale($this->language_to_locale);
        } catch (InvalidFormatException $exception) {
            CommonHelper::logException($exception);
            return null;
        }
    }

    /**
     * @param int|string $time
     * @param int $quantity
     * @param string $format
     * @return self|null
     */
    public function addition($time, int $quantity, string $format = 'month'): ?self
    {
        $carbon = \is_numeric($time) ? $this->getFromTimestamp($time) : $this->getFromFormat($time);

        if ($carbon === null) {
            return null;
        }

        switch ($format) {
            case 'second':
                return $carbon->addSeconds($quantity);
            case 'minute':
                return $carbon->addMinutes($quantity);
            case 'hour':
                return $carbon->addHours($quantity);
            case 'day':
                return $carbon->addDays($quantity);
            case 'month':
                return $carbon->addMonths($quantity);
            case 'year':
                return $carbon->addYears($quantity);
            default:
                return null;
        }
    }

    /**
     * @param int|string $time
     * @param int $quantity
     * @param string $format
     * @return self|null
     */
    public function subtraction($time, int $quantity, string $format = 'month'): ?self
    {
        $carbon = \is_numeric($time) ? $this->getFromTimestamp($time) : $this->getFromFormat($time);

        if ($carbon === null) {
            return null;
        }

        switch ($format) {
            case 'second':
                return $carbon->subSeconds($quantity);
            case 'minute':
                return $carbon->subMinutes($quantity);
            case 'hour':
                return $carbon->subHours($quantity);
            case 'day':
                return $carbon->subDays($quantity);
            case 'month':
                return $carbon->subMonths($quantity);
            case 'year':
                return $carbon->subYears($quantity);
            default:
                return null;
        }
    }
}
