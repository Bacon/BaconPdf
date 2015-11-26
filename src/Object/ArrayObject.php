<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Exception\OutOfRangeException;
use Countable;
use IteratorAggregate;
use SplFileObject;

/**
 * Array object as defined by section 3.2.5
 */
class ArrayObject extends AbstractObject implements IteratorAggregate, Countable
{
    /**
     * @var ObjectInterface[]
     */
    private $items = [];

    /**
     * @param ObjectInterface[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->append($item);
        }
    }

    /**
     * @param ObjectInterface $object
     */
    public function append(ObjectInterface $object)
    {
        $this->items[] = $object;
    }

    /**
     * @param int             $index
     * @param ObjectInterface $object
     */
    public function insert($index, ObjectInterface $object)
    {
        $size = count($this->items);

        if ($index < 0) {
            $index = max(0, $index + $size);
        } elseif ($index > $size) {
            $index = $size;
        }

        for ($i = $size; --$i >= $index; ) {
            $this->items[$i + 1] = $this->items[$i];
        }

        $this->items[$index] = $object;
    }

    /**
     * @param int $index
     */
    public function remove($index)
    {
        if (!array_key_exists($index, $this->items)) {
            throw new OutOfRangeException(sprintf(
                'Could not find an item with index %d',
                $index
            ));
        }

        array_splice($this->items, $index, 1);
    }

    /**
     * @param  int $index
     * @return ObjectInterface
     */
    public function get($index)
    {
        if (!array_key_exists($index, $this->items)) {
            throw new OutOfRangeException(sprintf(
                'Could not find an item with index %d',
                $index
            ));
        }

        return $this->items[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        $fileObject->fwrite('[');
        $size = count($this->items);

        for ($index = 0; $index < $size; ++$index) {
            $this->items[$index]->writeToStream($fileObject, $encryptionKey);
            $fileObject->fwrite(' ');
        }

        $fileObject->fseek(-1, SEEK_CUR);
        $fileObject->fwrite(']');
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $size = count($this->items);

        for ($index = 0; $index < $size; ++$index) {
            yield $index => $this->items[$index];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count($mode = 'COUNT_NORMAL')
    {
        return count($this->items);
    }
}
