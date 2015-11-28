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
 * Name object as defined by section 3.2.4
 */
class NameObject extends AbstractObject
{
    /**
     * List of characters which may occur unencoded.
     */
    const REGULAR_CHARACTERS = '!"$&\'*+,-.0123456789:;=?@ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz|~';

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        $fileObject->fwrite($this->name);
    }

    /**
     * @param  resource $handle
     * @return self
     * @throws PdfReadException
     */
    public static function readFromStream($handle)
    {
        $name = fread($handle, 1);

        if ('/' !== $name) {
            throw new PdfReadException('Name object does not start with a slash');
        }

        $name .= ReaderUtils::readUntilRegex($handle, '\s+|[\(\)<>\[\]{}/%]', true);

        return new self($name);
    }

    /**
     * Encodes the given string.
     *
     * @param  string $string
     * @return string
     */
    public static function encode($string)
    {
        $result     = '/';
        $nameLength = strlen($string);
        $currentPos = 0;

        while ($currentPos < $nameLength) {
            $portion = strspn($string, self::REGULAR_CHARACTERS, $currentPos);

            if ($portion > 0) {
                $result .= substr($string, $currentPos, $portion);
                $currentPos += $portion;
            }

            if ($currentPos < $nameLength) {
                $result .= sprintf('#%02X', ord($string[$currentPos]));
                ++$currentPos;
            }
        }

        return $result;
    }
}
