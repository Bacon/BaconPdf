<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Type;

use Bacon\Pdf\Object\AbstractObject;
use Bacon\Pdf\Object\LiteralStringObject;
use SplFileObject;

/**
 * Text string type as defined by section 3.8.1
 */
class TextStringType extends AbstractObject
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
        (new LiteralStringObject(
            "\xfe\xff" . iconv('UTF-8', 'UTF-16BE', $this->value)
        ))->writeToStream($fileObject, $encryptionKey);
    }
}
