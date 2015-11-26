<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Utils\EncryptionUtils;
use SplFileObject;

/**
 * Stream object as defined by section 3.2.7
 */
class StreamObject extends DictionaryObject
{
    /**
     * @var string
     */
    private $data;

    /**
     * @param string $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        $this['Length'] = new NumericObject(strlen($this->data));
        parent::writeToStream($fileObject, $encryptionKey);
        unset($this['Length']);

        $fileObject->fwrite("\nstream\n");
        $data = $this->data;

        if (null !== $encryptionKey) {
            $data = EncryptionUtils::rc4($encryptionKey, $data);
        }

        $fileObject->fwrite($data);
        $fileObject->fwrite("\nendstream");
    }
}
