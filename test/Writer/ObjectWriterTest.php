<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Writer;

use Bacon\Pdf\Writer\ObjectWriter;
use PHPUnit_Framework_TestCase as TestCase;
use SplFileObject;

/**
 * @covers \Bacon\Pdf\Writer\ObjectWriter
 */
class ObjectWriterTest extends TestCase
{
    /**
     * @var SplFileObject
     */
    private $fileObject;

    /**
     * @var ObjectWriter
     */
    private $objectWriter;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->fileObject = new SplFileObject('php://memory', 'w+b');
        $this->objectWriter = new ObjectWriter($this->fileObject);
    }

    public function testGetCurrentOffset()
    {
        $this->assertSame(0, $this->objectWriter->getCurrentOffset());
        $this->fileObject->fwrite('foo');
        $this->assertSame(3, $this->objectWriter->getCurrentOffset());
    }

    public function testWriteRawLine()
    {
        $this->objectWriter->writeRawLine('foo');
        $this->assertSame("foo\n", $this->getFileObjectData());
    }

    public function testStartDictionary()
    {
        $this->objectWriter->startDictionary();
        $this->assertSame('<<', $this->getFileObjectData());
    }

    public function testEndDictionary()
    {
        $this->objectWriter->endDictionary();
        $this->assertSame('>>', $this->getFileObjectData());
    }

    public function testStartArray()
    {
        $this->objectWriter->startArray();
        $this->assertSame('[', $this->getFileObjectData());
    }

    public function testEndArray()
    {
        $this->objectWriter->endArray();
        $this->assertSame(']', $this->getFileObjectData());
    }

    public function testWriteNull()
    {
        $this->objectWriter->writeNull();
        $this->assertSame('null', $this->getFileObjectData());
    }

    public function testWriteBooleanTrue()
    {
        $this->objectWriter->writeBoolean(true);
        $this->assertSame('true', $this->getFileObjectData());
    }

    public function testWriteBooleanFalse()
    {
        $this->objectWriter->writeBoolean(false);
        $this->assertSame('false', $this->getFileObjectData());
    }

    public function testWriteIntegerNumber()
    {
        $this->objectWriter->writeNumber(12);
        $this->assertSame('12', $this->getFileObjectData());
    }

    public function testWriteFloatNumber()
    {
        $this->objectWriter->writeNumber(12.3456789123);
        $this->assertSame('12.345679', $this->getFileObjectData());
    }

    public function testWriteName()
    {
        $this->objectWriter->writeName('foo');
        $this->assertSame('/foo', $this->getFileObjectData());
    }

    public function testWriteLiteralString()
    {
        $this->objectWriter->writeLiteralString('foo(bar\\baz)bat');
        $this->assertSame('(foo\\(bar\\\\baz\\)bat)', $this->getFileObjectData());
    }

    public function testWriteHexadecimalString()
    {
        $this->objectWriter->writeHexadecimalString('foo');
        $this->assertSame('<666f6f>', $this->getFileObjectData());
    }

    /**
     * @dataProvider whitespaceTestData
     */
    public function testWhitespaceHandling(array $methodCalls, $expectedData)
    {
        foreach ($methodCalls as $methodCall) {
            if (!array_key_exists(1, $methodCall)) {
                $methodCall[1] = [];
            }

            call_user_func_array([$this->objectWriter, $methodCall[0]], $methodCall[1]);
        }

        $this->assertSame($expectedData, $this->getFileObjectData());
    }

    /**
     * @return array
     */
    public function whitespaceTestData()
    {
        return [
            [[
                ['startDictionary'],
                ['endDictionary'],
            ], '<<>>'],
            [[
                ['startArray'],
                ['endArray'],
            ], '[]'],
            [[
                ['startDictionary'],
                ['writeNull'],
                ['endDictionary'],
            ], '<<null>>'],
            [[
                ['startArray'],
                ['writeNull'],
                ['endArray'],
            ], '[null]'],
            [[
                ['writeNull'],
                ['writeNull'],
            ], 'null null'],
            [[
                ['writeBoolean', [true]],
                ['writeBoolean', [false]],
            ], 'true false'],
            [[
                ['writeNumber', [1]],
                ['writeNumber', [1.1]],
            ], '1 1.1'],
            [[
                ['writeLiteralString', ['foo']],
                ['writeLiteralString', ['bar']],
            ], '(foo)(bar)'],
            [[
                ['writeHexadecimalString', ['foo']],
                ['writeHexadecimalString', ['bar']],
            ], '<666f6f><626172>'],
            [[
                ['writeNull'],
                ['writeRawLine', ['foo']],
            ], "nullfoo\n"],
        ];
    }

    /**
     * @return string
     */
    private function getFileObjectData()
    {
        $offset = $this->fileObject->ftell();
        $this->fileObject->fseek(0);
        $data = $this->fileObject->fread($offset);
        $this->fileObject->fseek($offset);
        return $data;
    }
}
