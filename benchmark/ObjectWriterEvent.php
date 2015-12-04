<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfBenchmark;

use Athletic\AthleticEvent;
use Bacon\Pdf\Writer\ObjectWriter;
use SplFileObject;

class ObjectWriterEvent extends AthleticEvent
{
    /**
     * @var ObjectWriter
     */
    private $objectWriter;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->objectWriter = new ObjectWriter(new SplFileObject('php://memory', 'w+'));
    }

    /**
     * @iterations 10000
     */
    public function writeRawLine()
    {
        $this->objectWriter->writeRawLine('foo');
    }

    /**
     * @iterations 10000
     */
    public function startDictionary()
    {
        $this->objectWriter->startDictionary();
    }

    /**
     * @iterations 10000
     */
    public function endDictionary()
    {
        $this->objectWriter->endDictionary();
    }

    /**
     * @iterations 10000
     */
    public function startArray()
    {
        $this->objectWriter->startArray();
    }

    /**
     * @iterations 10000
     */
    public function endArray()
    {
        $this->objectWriter->endArray();
    }

    /**
     * @iterations 10000
     */
    public function writeNull()
    {
        $this->objectWriter->writeNull();
    }

    /**
     * @iterations 10000
     */
    public function writeBoolean()
    {
        $this->objectWriter->writeBoolean(true);
    }

    /**
     * @iterations 10000
     */
    public function writeIntegerNumber()
    {
        $this->objectWriter->writeNumber(1);
    }

    /**
     * @iterations 10000
     */
    public function writeFloatNumber()
    {
        $this->objectWriter->writeNumber(1.1);
    }

    /**
     * @iterations 10000
     */
    public function writeName()
    {
        $this->objectWriter->writeName('foo');
    }

    /**
     * @iterations 10000
     */
    public function writeLiteralString()
    {
        $this->objectWriter->writeLiteralString('foo');
    }

    /**
     * @iterations 10000
     */
    public function writeHexadecimalString()
    {
        $this->objectWriter->writeHexadecimalString('foo');
    }
}
