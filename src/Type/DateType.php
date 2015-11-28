<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Type;

use DateTimeImmutable;
use SplFileObject;

/**
 * Date type as defined by section 3.8.3
 */
class DateType extends AbstractObject
{
    /**
     * @var DateTimeImmutable
     */
    private $dateTime;

    /**
     * @param DateTimeImmutable $dateTime
     */
    public function __construct(DateTimeImmutable $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        (new LiteralStringObject(
            substr_replace($this->dateTime->format('\D\:YmdHisO'), "'", -2, 0) . "'"
        ))->writeToStream($fileObject, $encryptionKey);
    }
}
