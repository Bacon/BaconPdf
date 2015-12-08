<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Writer;

use Bacon\Pdf\Exception\InvalidArgumentException;
use SplFileObject;

/**
 * Writer responsible for writing objects to a stream.
 *
 * While the PDF specification tells that there is a line limit of 255 characters, not even Adobe's own PDF library
 * respects this limit. We ignore it as well, as it imposes a huge impact on the performance of the writer.
 *
 * {@internal This is a very performance sensitive class, which is why some code may look duplicated. Before thinking
 * about refactoring these parts, take a good look and the supplied benchmarks and verify that your changes
 * do not affect the performance in a bad way. Keep in mind that the methods in this writer are called quite
 * often.}}
 */
class ObjectWriter
{
    /**
     * @var SplFileObject
     */
    private $fileObject;

    /**
     * @var bool
     */
    private $requiresWhitespace = false;

    /**
     * @var int
     */
    private $lastAllocatedObjectId = 0;

    /**
     * @var int[]
     */
    private $objectOffsets = [];

    /**
     * @param SplFileObject $fileObject
     */
    public function __construct(SplFileObject $fileObject)
    {
        $this->fileObject = $fileObject;
    }

    /**
     * Returns the current position in the file.
     *
     * @return int
     */
    public function getCurrentOffset()
    {
        return $this->fileObject->ftell();
    }

    /**
     * Writes a raw data line to the stream.
     *
     * A newline character is appended after the data. Keep in mind that you may still be after a token which requires
     * a following whitespace, depending on the context you are in.
     *
     * @param string $data
     */
    public function writeRawLine($data)
    {
        $this->fileObject->fwrite($data . "\n");
    }

    /**
     * Writes raw data to the stream.
     *
     * @param string $data
     */
    public function writeRaw($data)
    {
        $this->fileObject->fwrite($data);
    }

    /**
     * Returns all object offsets.
     *
     * @return int
     */
    public function getObjectOffsets()
    {
        return $this->objectOffsets;
    }

    /**
     * Allocates a new ID for an object.
     *
     * @return int
     */
    public function allocateObjectId()
    {
        return ++$this->lastAllocatedObjectId;
    }

    /**
     * Starts an object.
     *
     * If the object ID is omitted, a new one is allocated.
     *
     * @param  int|null $objectId
     * @return int
     */
    public function startObject($objectId = null)
    {
        if (null === $objectId) {
            $objectId = ++$this->lastAllocatedObjectId;
        }

        $this->objectOffsets[$objectId] = $this->fileObject->ftell();
        $this->fileObject->fwrite(sprintf("%d 0 obj\n", $objectId));

        return $objectId;
    }

    /**
     * Ends an object.
     */
    public function endObject()
    {
        $this->fileObject->fwrite("\nendobj\n");
    }

    /**
     * Starts a stream.
     */
    public function startStream()
    {
        $this->fileObject->fwrite("stream\n");
    }

    public function endStream()
    {
        $this->fileObject->fwrite("\nendstream\n");
    }

    /**
     * Writes an indirect reference
     *
     * @param int $objectId
     */
    public function writeIndirectReference($objectId)
    {
        if ($this->requiresWhitespace) {
            $this->fileObject->fwrite(sprintf(' %d 0 R', $objectId));
        } else {
            $this->fileObject->fwrite(sprintf('%d 0 R', $objectId));
        }

        $this->requiresWhitespace = true;
    }

    /**
     * Starts a dictionary.
     */
    public function startDictionary()
    {
        $this->fileObject->fwrite('<<');
        $this->requiresWhitespace = false;
    }

    /**
     * Ends a dictionary.
     */
    public function endDictionary()
    {
        $this->fileObject->fwrite('>>');
        $this->requiresWhitespace = false;
    }

    /**
     * Starts an array.
     */
    public function startArray()
    {
        $this->fileObject->fwrite('[');
        $this->requiresWhitespace = false;
    }

    /**
     * Ends an array.
     */
    public function endArray()
    {
        $this->fileObject->fwrite(']');
        $this->requiresWhitespace = false;
    }

    /**
     * Writes a null value.
     */
    public function writeNull()
    {
        if ($this->requiresWhitespace) {
            $this->fileObject->fwrite(' null');
        } else {
            $this->fileObject->fwrite('null');
        }

        $this->requiresWhitespace = true;
    }

    /**
     * Writes a boolean.
     *
     * @param bool $boolean
     */
    public function writeBoolean($boolean)
    {
        if ($this->requiresWhitespace) {
            $this->fileObject->fwrite($boolean ? ' true' : ' false');
        } else {
            $this->fileObject->fwrite($boolean ? 'true' : 'false');
        }

        $this->requiresWhitespace = true;
    }

    /**
     * Writes a number.
     *
     * @param  int|float $number
     * @throws InvalidArgumentException
     */
    public function writeNumber($number)
    {
        if ($this->requiresWhitespace) {
            $this->fileObject->fwrite(' ' . (rtrim(sprintf('%.6F', $number), '0.') ?: '0'));
        } else {
            $this->fileObject->fwrite(rtrim(sprintf('%.6F', $number), '0.') ?: '0');
        }

        $this->requiresWhitespace = true;
    }

    /**
     * Writes a name.
     *
     * @param string $name
     */
    public function writeName($name)
    {
        $this->fileObject->fwrite('/' . $name);
        $this->requiresWhitespace = true;
    }

    /**
     * Writes a literal string.
     *
     * The string itself is splitted into multiple lines after 248 characters. We chose that specific limit to avoid
     * splitting mutli-byte characters in half.
     *
     * @param string $string
     */
    public function writeLiteralString($string)
    {
        $this->fileObject->fwrite('(' . strtr($string, ['(' => '\\(', ')' => '\\)', '\\' => '\\\\']) . ')');
        $this->requiresWhitespace = false;
    }

    /**
     * Writes a hexadecimal string.
     *
     * @param string $string
     */
    public function writeHexadecimalString($string)
    {
        $this->fileObject->fwrite('<' . bin2hex($string) . '>');
        $this->requiresWhitespace = false;
    }
}
