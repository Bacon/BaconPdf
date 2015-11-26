<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Exception\DomainException;
use Bacon\Pdf\Exception\OutOfBoundsException;
use IteratorAggregate;
use SplObjectStorage;

/**
 * Storage for mapping indirect objects to concrete objects.
 */
final class ObjectStorage implements IteratorAggregate
{
    /**
     * @var ObjectInterface[]
     */
    private $objects = [];

    /**
     * @var SplObjectStorage|IndirectObject[]
     */
    private $indirectObjects = [];

    /**
     * @var int
     */
    private $objectCount = 0;

    public function __construct()
    {
        $this->indirectObjects = new SplObjectStorage();
    }

    /**
     * Adds an object to the storage.
     *
     * @param  ObjectInterface $object
     * @return IndirectObject
     */
    public function addObject(ObjectInterface $object)
    {
        $this->objects[] = $object;
        $this->indirectObjects[$object] = new IndirectObject(++$this->objectCount, 0, $this);
    }

    /**
     * @return int
     */
    public function reserveSlot()
    {
        $this->objects[] = null;
        return ++$this->objectCount;
    }

    /**
     * @param  IndirectObject $indirectObject
     * @throws DomainException
     */
    public function fillSlot(IndirectObject $indirectObject)
    {
        if ($indirectObject->getObjectStorage() !== $this) {
            throw new DomainException('Object storage of indirect object must equal this object storage');
        }

        $index = $indirectObject->getId() - 1;

        if (!isset($this->objects[$index])) {
            throw new DomainException(sprintf('No reserved slot for id %d found', $id));
        }

        $this->objects[$index] = $indirectObject;
    }

    /**
     * @param  IndirectObject $indirectObject
     * @return ObjectInterface
     */
    public function getObject(IndirectObject $indirectObject)
    {
        if ($indirectObject->getObjectStorage() !== $this) {
            throw new DomainException('Object storage of indirect object must equal this object storage');
        }

        return $this->objects[$indirectObject->getId() - 1];
    }

    /**
     * @param  ObjectInterface $object
     * @return IndirectObject
     * @throws OutOfBoundsException
     */
    public function getIndirectObject(ObjectInterface $object)
    {
        if (!$this->indirectObjects->contains($object)) {
            throw new OutOfBoundsException('Object is not registered in the storage');
        }

        return $this->indirectObjects[$object];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->objects as $index => $object) {
            if (null === $object) {
                throw new DomainException(sprintf('Object slot for id %d was reserved but not filled', $index + 1));
            }

            yield $index => $object;
        }
    }
}
