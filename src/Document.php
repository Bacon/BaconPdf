<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf;

use Bacon\Pdf\Object\ArrayObject;
use Bacon\Pdf\Object\DictionaryObject;
use Bacon\Pdf\Object\HexadecimalStringObject;
use Bacon\Pdf\Object\IndirectObject;
use Bacon\Pdf\Object\LiteralStringObject;
use Bacon\Pdf\Object\NameObject;
use Bacon\Pdf\Object\NumericObject;
use Bacon\Pdf\Object\ObjectInterface;
use Bacon\Pdf\Object\ObjectStorage;
use Bacon\Pdf\Object\StreamObject;
use Bacon\Pdf\Utils\EncryptionUtils;
use Doctrine\Instantiator\Exception\UnexpectedValueException;
use SplFileObject;
use SplObjectStorage;

class Document
{
    const HEADER = '%PDF-1.7';

    /**
     * @var ObjectStorage
     */
    private $objects;

    /**
     * @var IndirectObject
     */
    private $pages;

    /**
     * @var IndirectObject
     */
    private $info;

    /**
     * @var IndirectObject
     */
    private $root;

    /**
     * @var IndirectObject|null
     */
    private $encrypt;

    /**
     * @var string|null
     */
    private $encryptionKey;

    /**
     * @var HexadecimalStringObject
     */
    private $firstId;

    /**
     * @var HexadecimalStringObject
     */
    private $secondId;

    /**
     * Creates a new PDF document.
     */
    public function __construct()
    {
        $this->objects = new ObjectStorage();

        $pages = new DictionaryObject();
        $pages['Type'] = new NameObject('Pages');
        $pages['Count'] = new NumericObject(0);
        $pages['Kids'] = new ArrayObject();
        $this->pages = $this->objects->addObject($pages);

        $info = new DictionaryObject();
        $info['Producer'] = new LiteralStringObject('BaconPdf');
        $this->info = $this->objects->addObject($info);

        $root = new DictionaryObject();
        $root['Type'] = new NameObject('Catalog');
        $root['Pages'] = $this->pages;
        $this->root = $this->objects->addObject($root);

        $this->firstId  = new HexadecimalStringObject($this->generateFileIdentifier());
        $this->secondId = new HexadecimalStringObject($this->generateFileIdentifier());
    }

    /**
     * Enables encryption for the document.
     *
     * @param string      $userPassword
     * @param string|null $ownerPassword
     * @param bool        $use128bit
     */
    public function enableEncryption($userPassword, $ownerPassword = null, $use128bit = true)
    {
        if (null === $ownerPassword) {
            $ownerPassword = $userPassword;
        }

        if ($use128bit) {
            $algorithm = 2;
            $revision  = 3;
            $keyLength = 128 / 8;
        } else {
            $algorithm = 1;
            $revision  = 2;
            $keyLength = 40 / 8;
        }

        $permissions = -1;
        $ownerEntry  = EncryptionUtils::computeOwnerEntry($ownerPassword, $userPassword, $revision, $keyLength);

        if (2 === $revision) {
            list($userEntry, $key) = EncryptionUtils::computeUserEntryRev2(
                $userPassword,
                $ownerEntry,
                $revision,
                $this->firstId->getValue()
            );
        } else {
            list($userEntry, $key) = EncryptionUtils::computeUserEntryRev3OrGreater(
                $userPassword,
                $revision,
                $keyLength,
                $ownerEntry,
                $permissions,
                $this->firstId->getValue()
            );
        }

        $encrypt = new DictionaryObject();
        $encrypt['Filter'] = new NameObject('Standard');
        $encrypt['V'] = new NumericObject($algorithm);

        if (2 === $algorithm) {
            $encrypt['Length'] = new NumericObject($keyLength * 8);
        }

        $encrypt['R'] = new NumericObject($revision);
        $encrypt['O'] = new HexadecimalStringObject($ownerEntry);
        $encrypt['U'] = new HexadecimalStringObject($userEntry);
        $encrypt['P'] = new NumericObject($permissions);

        $this->encrypt = $this->objects->addObject($encrypt);
        $this->encryptionKey = $key;
    }

    /**
     * Writes the document to a file object.
     *
     * @param SplFileObject $fileObject
     */
    public function write(SplFileObject $fileObject)
    {
        $this->resolveCircularReferences();
        $this->writeHeader($fileObject);

        $objectOffsets = $this->writeObjects($fileObject);
        $xrefOffset    = $fileObject->ftell();

        $this->writeXrefTable($fileObject, $objectOffsets);
        $this->writeTrailer($fileObject);
        $this->writeFooter($fileObject, $xrefOffset);
    }

    /**
     * Writes the document to a file.
     *
     * @param string $filename
     */
    public function writeToFile($filename)
    {
        $this->write(new SplFileObject($filename, 'wb'));
    }

    /**
     * Outputs to document directly.
     */
    public function output()
    {
        $this->write(new SplFileObject('php://stdout', 'wb'));
    }

    /**
     * Returns the document as a string.
     *
     * @return string
     */
    public function toString()
    {
        $fileObject = new SplFileObject('php://memory', 'w+b');
        $this->write($fileObject);

        $fileObject->fseek(0, SEEK_END);
        $length = $fileObject->ftell();
        $fileObject->fseek(0);

        return $fileObject->fread($length);
    }

    /**
     * Resolves circular references in page objects.
     */
    private function resolveCircularReferences()
    {
        $externalReferenceMap = new SplObjectStorage();

        foreach ($this->objects as $id => $object) {
            if (!$object instanceof Page || null === $object->getIndirectReference()) {
                continue;
            }

            $indirectObject = $object->getIndirectReference();
            $objectStorage  = $indirectObject->getObjectStorage();
            $generation     = $indirectObject->getGeneration();

            if (!$externalReferenceMap->contains($objectStorage)) {
                $externalReferenceMap->attach($objectStorage, []);
            }

            if (!array_key_exists($generation, $externalReferenceMap[$objectStorage])) {
                $externalReferenceMap[$objectStorage][$generation] = [];
            }

            $externalReferenceMap[$objectStorage][$generation][$id] = new IndirectObject($id, 0, $this->objects);
        }

        $stack = [];
        $this->sweepIndirectReferences($externalReferenceMap, $this->root, $stack);
    }

    /**
     * @param  SplObjectStorage $externalReferenceMap
     * @param  ObjectInterface  $object
     * @param  array            $stack
     * @return ObjectInterface
     */
    private function sweepIndirectReferences(
        SplObjectStorage $externalReferenceMap,
        ObjectInterface $object,
        array &$stack
    ) {
        if ($object instanceof DictionaryObject || $object instanceof ArrayObject) {
            foreach ($object as $index => $childObject) {
                $childObject = $this->sweepIndirectReferences($externalReferenceMap, $childObject, $stack);

                if ($childObject instanceof StreamObject) {
                    $childObject = $this->objects->addObject($childObject);
                }

                $object[$index] = $childObject;
            }

            return $object;
        }

        if ($object instanceof IndirectObject) {
            $objectStorage = $object->getObjectStorage();

            if ($objectStorage === $this->objects) {
                if (in_array($object->getId(), $stack)) {
                    return $object;
                }

                $stack[] = $object->getId();
                $this->sweepIndirectReferences($externalReferenceMap, $this->objects->getObject($object), $stack);

                return $object;
            }

            $id         = $object->getId();
            $generation = $object->getGeneration();

            if (!$externalReferenceMap->contains($objectStorage)) {
                $externalReferenceMap->attach($objectStorage, []);
            }

            $map = $externalReferenceMap[$objectStorage];

            if (isset($externalReferenceMap[$objectStorage][$generation][$id])) {
                return $map[$generation][$id];
            }

            $newObject         = $objectStorage->getObject($object);
            $newId             = $this->objects->reserveSlot();
            $newIndirectObject = new IndirectObject($newId, 0, $this->objects);

            if (!array_key_exists($generation, $externalReferenceMap[$objectStorage])) {
                $externalReferenceMap[$objectStorage][$generation] = [];
            }

            $externalReferenceMap[$objectStorage][$generation][$id] = $newIndirectObject;
            $sweepedIndirectObject = $this->sweepIndirectReferences($externalReferenceMap, $newObject, $stack);

            if (!$sweepedIndirectObject instanceof IndirectObject) {
                throw new UnexpectedValueException(sprintf(
                    'Expected sweeped object to be of type %s, got %s',
                    IndirectObject::class,
                    get_class($sweepedIndirectObject)
                ));
            }

            $this->objects->fillSlot($sweepedIndirectObject);

            return $newIndirectObject;
        }

        return $object;
    }

    /**
     * Writes the file header.
     *
     * @param SplFileObject $fileObject
     */
    private function writeHeader(SplFileObject $fileObject)
    {
        $fileObject->fwrite(static::HEADER . "\n");
        $fileObject->fwrite("%\xff\xff\xff\xff\n");
    }

    /**
     * Writes all objects.
     *
     * @param  SplFileObject $fileObject
     * @return array
     */
    private function writeObjects(SplFileObject $fileObject)
    {
        $objectOffsets = [];

        foreach ($this->objects as $object) {
            $indirectObject = $this->objects->getIndirectObject($object);
            $id = $indirectObject->getId();
            $objectOffsets[] = $fileObject->ftell();
            $fileObject->fwrite(sprintf("%d 0 obj\n", $id));

            $key = null;

            if (null !== $this->encrypt && $id !== $this->encrypt->getId()) {
                $key = substr(hex2bin(md5(
                    $this->encryptionKey
                    . substr(pack('V', $id), 0, 3)
                    . substr(pack('V', $indirectObject->getGeneration()), 0, 2)
                )), 0, min(16, strlen($this->encryptionKey) + 5));
            }

            $object->writeToStream($fileObject, $key);
            $fileObject->fwrite("\nendobj\n");
        }

        return $objectOffsets;
    }

    /**
     * Writes the xref table.
     *
     * @param SplFileObject $fileObject
     * @param array         $objectOffsets
     */
    private function writeXrefTable(SplFileObject $fileObject, array $objectOffsets)
    {
        $fileObject->fwrite("xref\n");
        $fileObject->fwrite(sprintf("0 %d\n", count($this->objects) + 1));
        $fileObject->fwrite(sprintf("%010d %05d f \n", 0, 65535));

        foreach ($objectOffsets as $offset) {
            $fileObject->fwrite(sprintf("%010d %05d n \n", $offset, 0));
        }
    }

    /**
     * Writes the trailer.
     *
     * @param SplFileObject $fileObject
     */
    private function writeTrailer(SplFileObject $fileObject)
    {
        $fileObject->fwrite("trailer\n");
        $trailer = new DictionaryObject();
        $trailer['Size'] = new NumericObject(count($this->objects) + 1);
        $trailer['Root'] = $this->root;
        $trailer['Info'] = $this->info;
        $trailer['Id'] = new ArrayObject([$this->firstId, $this->secondId]);
        $trailer->writeToStream($fileObject, null);
    }

    /**
     * Writes the file footer.
     *
     * @param SplFileObject $fileObject
     * @param int           $xrefOffset
     */
    private function writeFooter(SplFileObject $fileObject, $xrefOffset)
    {
        $fileObject->fwrite("\n");
        $fileObject->fwrite("startxref\n");
        $fileObject->fwrite(sprintf("%d\n", $xrefOffset));
        $fileObject->fwrite("%%%EOF\n");
    }

    /**
     * Computes a file identifier.
     *
     * @return string
     */
    private function generateFileIdentifier()
    {
        return hex2bin(md5(microtime()));
    }
}
