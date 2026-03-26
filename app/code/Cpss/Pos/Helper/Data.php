<?php

namespace Cpss\Pos\Helper;

use DateTime;
use DateTimeZone;

class Data
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface
     */
    protected $utcConverter;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface $utcConverter
    ) {
        $this->timezone = $timezone;
        $this->utcConverter = $utcConverter;
    }

    /**
     * Convert Timezone
     *
     * @param string $dateTime
     * @param string $saveUTC
     * @param string $format
     * @return string
     */
    public function convertTimezone($dateTime, $timeZone = "UTC", $format = null)
    {
        $jstTimezone = "Asia/Tokyo";
        $defaultTimezone = "UTC";

        switch ($timeZone) {
            case "JST-UTC":
                return $this->timezoneConvert($dateTime, $jstTimezone, $defaultTimezone, $format);
            case "UTC-JST":
                // return $this->timezoneConvert($dateTime, $defaultTimezone, $jstTimezone, $format);
                return $this->timezone->date(new DateTime($dateTime))->format($format ?? 'Y-m-d H:i:s');
                break;
            // These 2 options are included in case of dates that needs formatting only.
            // e.g. From CPSS dates
            case "UTC":
                return $this->timezoneConvert($dateTime, $defaultTimezone, $defaultTimezone, $format);
                break;
            case "JST":
                return $this->timezoneConvert($dateTime, $jstTimezone, $jstTimezone, $format);
                break;
            default:
                return $this->timezoneConvert($dateTime, $defaultTimezone, $timeZone, $format);
                break;
        }
    }

    protected function timezoneConvert($dateTime, $from, $to, $format = null)
    {
        $date = new DateTime($dateTime, new DateTimeZone($from));
        $date->setTimezone(new DateTimeZone($to));
        return $date->format($format ?? 'Y-m-d H:i:s');
    }

    public function convertEcTimeUtcJst($date, $format = null)
    {
        return $this->timezoneConvert($date, "UTC", "Asia/Tokyo", $format);
    }
}
