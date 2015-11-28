<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Exception\PdfReadException;
use Bacon\Pdf\Utils\ReaderUtils;
use SplFileObject;

/**
 * Numeric object as defined by section 3.2.2
 */
class NumericObject extends AbstractObject
{
    /**
     * @var int|float
     */
    private $value;

    /**
     * @param int|float $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject)
    {
        if (false === strpos($this->value, '.')) {
            $fileObject->fwrite($this->value);
            return;
        }

        $fileObject->fwrite(sprintf('%F', $this->value));
    }

    /**
     * @param  resource $handle
     * @return self
     * @throws PdfReadException
     */
    public static function readFromStream($handle)
    {
        $number = ReaderUtils::readUntilRegex($handle, '[^+-.0-9]');

        if (false === strpos($number, '.')) {
            return new self((int) $number);
        }

        return new self((float) $number);
    }
}
