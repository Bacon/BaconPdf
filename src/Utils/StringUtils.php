<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Utils;

use DateTimeInterface;

final class StringUtils
{
    public function __construct()
    {
    }

    /**
     * Encodes a string according to section 3.8.1.
     *
     * @param  string $string
     * @return string
     */
    public static function encodeString($string)
    {
        return "\xfe\xff" . iconv('UTF-8', 'UTF-16BE', $string);
    }

    /**
     * Formats a date according to section 3.8.3.
     *
     * @param  DateTimeInterface $dateTime
     * @return string
     */
    public static function formatDateTime(DateTimeInterface $dateTime)
    {
        $timeString = $dateTime->format('\D\:YmdHis');

        if (0 === $dateTime->getTimezone()->getOffset()) {
            return $timeString . 'Z';
        }

        return $timeString . strtr(':', "'", $dateTime->format('P')) . "'";
    }
}
