<?php
/**
 * Bacon\Pdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

use Bacon\Pdf\Exception\PdfReadException;
use SplFileObject;

/**
 * Indirect object as defined by section 3.2.9
 */
class IndirectObject implements ObjectInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $generation;

    /**
     * @var ObjectStorage
     */
    protected $objectStorage;

    /**
     * @param int           $id
     * @param int           $generation
     * @param ObjectStorage $objectStorage
     */
    public function __construct($id, $generation, ObjectStorage $objectStorage)
    {
        $this->id            = $id;
        $this->generation    = $generation;
        $this->objectStorage = $objectStorage;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getGeneration()
    {
        return $this->generation;
    }

    /**
     * @return ObjectStorage
     */
    public function getObjectStorage()
    {
        return $this->objectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getObject()
    {
        return $this->objectStorage->getObject($this)->getObject();
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream(SplFileObject $fileObject)
    {
        $fileObject->fwrite(sprintf('%d %d R', $this->id, $this->generation));
    }

    /**
     * @param  resource $handle
     * @return self
     * @throws PdfReadException
     */
    public static function readFromStream($handle)
    {
    }
}
