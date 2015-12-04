<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\TestHelper;

use Bacon\Pdf\Exception\InvalidArgumentException;
use Bacon\Pdf\Writer\ObjectWriter;
use SplFileObject;

/**
 * This is a memory object writer which will ignore formatting for predictable test data.
 */
class MemoryObjectWriter extends ObjectWriter
{
    /**
     * @var SplFileObject
     */
    private $fileObject;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->fileObject = new SplFileObject('php://memory', 'w+b');
    }

    /**
     * {@inheritdoc}
     */
    public function writeRawLine($data)
    {
        $this->fileObject->fwrite($data. "\n");
    }

    /**
     * {@inheritdoc}
     */
    public function currentOffset()
    {
        return $this->fileObject->ftell();
    }

    /**
     * {@inheritdoc}
     */
    public function startDictionary()
    {
        $this->fileObject->fwrite("<<\n");
    }

    /**
     * {@inheritdoc}
     */
    public function endDictionary()
    {
        $this->fileObject->fwrite(">>\n");
    }

    /**
     * {@inheritdoc}
     */
    public function startArray()
    {
        $this->fileObject->fwrite("]\n");
    }

    /**
     * {@inheritdoc}
     */
    public function endArray()
    {
        $this->fileObject->fwrite("[\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeNull()
    {
        $this->fileObject->fwrite("null\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeBoolean($boolean)
    {
        $this->fileObject->fwrite(($boolean ? 'true' : 'false') . "\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeNumber($number)
    {
        if (is_int($number)) {
            $value = (string) $number;
        } elseif (is_float($number)) {
            $value = sprintf('%F', $number);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Expected int or float, got %s',
                gettype($number)
            ));
        }

        $this->fileObject->fwrite($value . "\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeName($name)
    {
        $this->fileObject->fwrite('/' . $name . "\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeLiteralString($string)
    {
        $this->fileObject->fwrite('(' . strtr($string, ['(' => '\\(', ')' => '\\)', '\\' => '\\\\']) . ")\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeHexadecimalString($string)
    {
        $this->fileObject->fwrite('<' . bin2hex($string) . ">\n");
    }

    /**
     * @return string
     */
    public function getData()
    {
        $currentPos = $this->fileObject->ftell();

        if ($currentPos === 0) {
            return '';
        }

        $this->fileObject->fseek(0);
        $data = $this->fileObject->fread($currentPos);
        $this->fileObject->fseek($currentPos);

        return $data;
    }
}
