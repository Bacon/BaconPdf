<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Exception\OutOfBoundsException;
use SplFileObject;

/**
 * Array object as defined by section 3.2.5
 */
class ArrayObject extends AbstractObject
{
    /**
     * @var ObjectInterface[]
     */
    private $items;

    /**
     * @param array $items
     */
    public function __construct(array $items)
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
     * @param  int $index
     * @return ObjectInterface
     */
    public function get($index)
    {
        if (!array_key_exists($index, $this->items)) {
            throw new OutOfBoundsException(sprintf(
                'Could not find an item with index %d',
                $index
            ));
        }

        return $this->items[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject)
    {
        $fileObject->fwrite('[');

        foreach ($this->items as $value) {
            $fileObject->fwrite(' ');
            $value->writeToStream($fileObject);
        }

        $fileObject->fwrite(']');
    }
}
