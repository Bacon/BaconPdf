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

/**
 * Null encryption which will just return plaintext.
 */
final class NullEncryption implements EncryptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function encrypt($plaintext, $objectNumber, $generationNumber)
    {
        return $plaintext;
    }

    /**
     * {@inheritdoc}
     */
    public function writeEncryptDictionary(ObjectWriter $objectWriter)
    {
    }
}
