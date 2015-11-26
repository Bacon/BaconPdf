<?php
/**
 * Bacon\Pdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Exception\PdfReadException;
use SplFileObject;

/**
 * Boolean object as defined by section 3.2.1
 */
class BooleanObject extends AbstractObject
{
    /**
     * @var bool
     */
    private $value;

    /**
     * @param bool $value
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
        $fileObject->fwrite($this->value ? 'true' : 'false');
    }

    /**
     * @param  resource $handle
     * @return self
     * @throws PdfReadException
     */
    public static function readFromStream($handle)
    {
        $word = fread($handle, 4);

        if ('true' === $word) {
            return new self(true);
        } elseif ('false' === $word . fread($handle, 1)) {
            return new self(false);
        }

        throw new PdfReadException('Could not read Boolean object');
    }
}
