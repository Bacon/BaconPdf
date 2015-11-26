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
 * Literal string object as defined by section 3.2.3
 */
class LiteralStringObject extends AbstractObject
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
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        $value = $this->value;

        if (null !== $encryptionKey) {
            (new HexadecimalStringObject($value))->writeToStream($fileObject, $encryptionKey);
            return;
        }

        $fileObject->fwrite('(');
        $fileObject->fwrite(chunk_split(strtr($value, [
            '(' => '\\(',
            ')' => '\\)',
            '\\' => '\\\\',
        ]), 255, "\\\n"));
        $fileObject->fwrite(')');
    }
}
