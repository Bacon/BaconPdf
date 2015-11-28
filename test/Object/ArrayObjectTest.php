<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Object;

use Bacon\Pdf\Exception\OutOfRangeException;
use Bacon\Pdf\Object\ArrayObject;
use Bacon\Pdf\Object\ObjectInterface;
use PHPUnit_Framework_TestCase as TestCase;
use SplFileObject;

/**
 * @covers \Bacon\Pdf\Object\ArrayObject
 */
class ArrayObjectTest extends TestCase
{
    public function testArbitraryItemsAreInsertedInOrder()
    {
        $object1 = $this->prophesize(ObjectInterface::class)->reveal();
        $object2 = $this->prophesize(ObjectInterface::class)->reveal();

        $arrayObject = new ArrayObject([
            'foo' => $object1,
            'bar' => $object2,
        ]);

        $this->assertSame(2, count($arrayObject));
        $this->assertSame($object1, $arrayObject->get(0));
        $this->assertSame($object2, $arrayObject->get(1));
    }

    public function testAppendAddsToEnd()
    {
        $object1 = $this->prophesize(ObjectInterface::class)->reveal();
        $object2 = $this->prophesize(ObjectInterface::class)->reveal();

        $arrayObject = new ArrayObject();
        $arrayObject->append($object1);
        $arrayObject->append($object2);

        $this->assertSame($object1, $arrayObject->get(0));
        $this->assertSame($object2, $arrayObject->get(1));
    }

    /**
     * @dataProvider getInsertData
     * @param int $index
     * @param int $object1pos
     * @param int $object2pos
     * @param int $object3pos
     */
    public function testInsert($index, $object1pos, $object2pos, $object3pos)
    {
        $object1 = $this->prophesize(ObjectInterface::class)->reveal();
        $object2 = $this->prophesize(ObjectInterface::class)->reveal();
        $object3 = $this->prophesize(ObjectInterface::class)->reveal();

        $arrayObject = new ArrayObject([$object1, $object2]);
        $arrayObject->insert($index, $object3);

        $this->assertSame($object1, $arrayObject->get($object1pos));
        $this->assertSame($object2, $arrayObject->get($object2pos));
        $this->assertSame($object3, $arrayObject->get($object3pos));
    }

    /**
     * @return array
     */
    public function getInsertData()
    {
        return [
            'insert-at-beginning' => [
                0, 1, 2, 0
            ],
            'insert-at-end' => [
                2, 0, 1, 2
            ],
            'insert-with-overflow-index' => [
                3, 0, 1, 2
            ],
            'insert-with-negative-index' => [
                -1, 0, 2, 1
            ],
            'insert-with-negative-undeflow-index' => [
                -3, 1, 2, 0
            ],
        ];
    }

    public function testRemove()
    {
        $object1 = $this->prophesize(ObjectInterface::class)->reveal();
        $object2 = $this->prophesize(ObjectInterface::class)->reveal();
        $object3 = $this->prophesize(ObjectInterface::class)->reveal();

        $arrayObject = new ArrayObject([$object1, $object2, $object3]);
        $arrayObject->remove(1);

        $this->assertSame(2, count($arrayObject));
        $this->assertSame($object1, $arrayObject->get(0));
        $this->assertSame($object3, $arrayObject->get(1));
    }

    public function testRemoveOutOfRangeIndex()
    {
        $this->setExpectedException(OutOfRangeException::class, 'Could not find an item with index 0');
        (new ArrayObject())->remove(0);
    }

    public function testGetOutOfRangeIndex()
    {
        $this->setExpectedException(OutOfRangeException::class, 'Could not find an item with index 0');
        (new ArrayObject())->get(0);
    }

    public function testIterator()
    {
        $object1 = $this->prophesize(ObjectInterface::class)->reveal();
        $object2 = $this->prophesize(ObjectInterface::class)->reveal();

        $arrayObject = new ArrayObject([$object1, $object2]);
        $result      = [];

        foreach ($arrayObject as $index => $object) {
            $result[$index] = $object;
        }

        $this->assertSame([$object1, $object2], $result);
    }

    public function testWriteToStream()
    {
        $fileObject = new SplFileObject('php://memory', 'w+b');

        $object1 = $this->prophesize(ObjectInterface::class);
        $object1->writeToStream($fileObject, 'foo')->will(function (array $args) { $args[0]->fwrite('1'); });
        $object2 = $this->prophesize(ObjectInterface::class);
        $object2->writeToStream($fileObject, 'foo')->will(function (array $args) { $args[0]->fwrite('2'); });

        $arrayObject = new ArrayObject([$object1->reveal(), $object2->reveal()]);
        $arrayObject->writeToStream($fileObject, 'foo');

        $fileObject->fseek(0, SEEK_END);
        $length = $fileObject->ftell();
        $fileObject->fseek(0);
        $result = $fileObject->fread($length);

        $this->assertSame('[1 2]', $result);
    }
}
