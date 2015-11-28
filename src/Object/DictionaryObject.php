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
use IteratorAggregate;
use SplFileObject;

/**
 * Dictionary object as defined by section 3.2.6
 */
class DictionaryObject extends AbstractObject implements IteratorAggregate
{
    /**
     * @var ObjectInterface[]
     */
    private $objects;

    /**
     * @param ObjectInterface[] $objects
     */
    public function __construct(array $objects)
    {
        foreach ($objects as $key => $object) {
            $this->set($key, $object);
        }
    }

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
     * Checks whether an object with a given key eixsts.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->objects);
    }

    /**
     * Returns an object with the given key.
     *
     * @param  string $key
     * @return ObjectInterface
     * @throws OutOfBoundsException
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->objects)) {
            throw new OutOfBoundsException(sprintf(
                'Could not find an object with key %s',
                $key
            ));
        }

        return $this->objects[$key];
    }

    /**
     * Sets an object with the given key.
     *
     * @param string          $key
     * @param ObjectInterface $object
     */
    public function set($key, ObjectInterface $object)
    {
        $this->objects[$key] = $object;
    }

    /**
     * Removes an object with the given key.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->objects[$key]);
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
