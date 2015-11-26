<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

/**
 * Hexadecimal string object as defined by section 3.2.3
 */
class HexadecimalStringObject extends AbstractObject
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        $value = $this->value;

        if (null !== $encryptionKey) {
            $value = \Bacon\Pdf\Utils\EncryptionUtils::rc4($encryptionKey, $value);
        }

        $fileObject->fwrite('<');
        $fileObject->fwrite(chunk_split(bin2hex($value), 255, "\n"));
        $fileObject->fwrite('>');
    }
}
