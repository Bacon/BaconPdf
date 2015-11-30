<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Encryption;

use Bacon\Pdf\Writer\ObjectWriter;

interface EncryptionInterface
{
    /**
     * Encrypts a string.
     *
     * @param  string $plaintext
     * @param  int    $objectNumber
     * @param  int    $generationNumber
     * @return string
     */
    public function encrypt($plaintext, $objectNumber, $generationNumber);

    /**
     * Writes the encrypt dictionary.
     *
     * @param ObjectWriter $objectWriter
     */
    public function writeEncryptDictionary(ObjectWriter $objectWriter);
}
