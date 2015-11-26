<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use SplFileObject;

interface ObjectInterface
{
    /**
     * Get the actual object.
     *
     * All normal objects will just return themself, while indirect objects will return the actual object.
     *
     * @return ObjectInterface
     */
    public function getObject();

    /**
     * Writes the object out to $handle in PDF representation.
     *
     * @param SplFileObject $fileObject
     * @param string|null   $encryptionKey
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey);
}
