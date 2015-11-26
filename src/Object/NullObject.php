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
 * Null object as defined by section 3.2.8
 */
class NullObject extends AbstractObject
{
    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject)
    {
        $fileObject->fwrite('null');
    }

    /**
     * @param  resource $handle
     * @return self
     * @throws PdfReadException
     */
    public static function readFromStream($handle)
    {
        $word = fread($handle, 4);

        if ('null' === $word) {
            return new self();
        }

        throw new PdfReadException('Could not read Null object');
    }
}
