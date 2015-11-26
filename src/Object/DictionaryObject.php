<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use ArrayAccess;
use Bacon\Pdf\Exception\InvalidArgumentException;
use IteratorAggregate;
use SplFileObject;

/**
 * Dictionary object as defined by section 3.2.6
 */
class DictionaryObject extends AbstractObject implements ArrayAccess, IteratorAggregate
{
    /**
     * @var ObjectInterface[]
     */
    private $items;

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject, $encryptionKey)
    {
        $fileObject->fwrite("<<\n");

        foreach ($this->items as $key => $value) {
            (new NameObject($key))->writeToStream($fileObject, $encryptionKey);
            $fileObject->fwrite(' ');
            $value->writeToStream($fileObject, $encryptionKey);
        }

        $fileObject->fwrite('>>');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof ObjectInterface) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of type ObjectInterface, %s given',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }

        $this->items[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->items as $index => $item) {
            yield $index => $item;
        }
    }
}
