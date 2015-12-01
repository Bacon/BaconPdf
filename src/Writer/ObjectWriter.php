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

class ObjectWriter
{
    /**
     * @var SplFileObject|null
     */
    private $fileObject;

    /**
     * @var int
     */
    private $currentLineLength = 0;

    /**
     * @var bool
     */
    private $requiresWhitespace = false;

    /**
     * @param SplFileObject $fileObject
     */
    public function __construct(SplFileObject $fileObject)
    {
        $this->fileObject = $fileObject;
    }

    /**
     * Writes raw data line to the stream.
     *
     * This method does not obey the normal line length limit, so you have to take care of that yourself. Note that the
     * writer may still be on an active line, so take that into account as well.
     *
     * @param string $data
     */
    public function writeRawLine($data)
    {
        if (null === $this->fileObject) {
            throw new WriterClosedException('The writer object was closed');
        }

        $this->fileObject->fwrite($data. "\n");
        $this->currentLineLength = 0;
    }

    /**
     * Ensures that the writer is on a blank line.
     */
    public function ensureBlankLine()
    {
        if ($this->currentLineLength === 0) {
            return;
        }

        $this->fileObject->fwrite("\n");
        $this->currentLineLength = 0;
    }

    /**
     * Returns the current position in the file.
     *
     * @return int
     */
    public function currentOffset()
    {
        return $this->fileObject->ftell();
    }

    /**
     * Starts a dictionary.
     */
    public function startDictionary()
    {
        $this->writeData('<<', false);
        $this->requiresWhitespace = false;
    }

    /**
     * Ends a dictionary.
     */
    public function endDictionary()
    {
        $this->writeData('>>', false);
        $this->requiresWhitespace = false;
    }

    /**
     * Starts an array.
     */
    public function startArray()
    {
        $this->writeData('[', false);
        $this->requiresWhitespace = false;
    }

    /**
     * Ends an array.
     */
    public function endArray()
    {
        $this->writeData(']', false);
        $this->requiresWhitespace = false;
    }

    /**
     * Writes a null value.
     */
    public function writeNull()
    {
        $this->writeData('null', true);
        $this->requiresWhitespace = true;
    }

    /**
     * Writes a boolean.
     *
     * @param bool $boolean
     */
    public function writeBoolean($boolean)
    {
        $this->writeData($boolean ? 'true' : 'false', true);
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
        if (is_int($number)) {
            $value = (string) $number;
        } elseif (is_float($number)) {
            $value = sprintf('%F', $number);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Expected int or float, got %s',
                gettype($number)
            ));
        }

        $this->writeData($value, true);
        $this->requiresWhitespace = true;
    }

    /**
     * Writes a name.
     *
     * @param string $name
     */
    public function writeName($name)
    {
        $this->writeData('/' . $name, false);
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
        $this->writeData('(' . chunk_split(strtr($string, [
            '(' => '\\(',
            ')' => '\\)',
            '\\' => '\\\\',
        ]), 248, "\\\n") . ')', false, "\\\n");
        $this->requiresWhitespace = false;
    }

    /**
     * Writes a hexadecimal string.
     *
     * @param string $string
     */
    public function writeHexadecimalString($string)
    {
        $this->writeData('<' . chunk_split(bin2hex($string), 248, "\n") . '>', false, "\n");
        $this->requiresWhitespace = false;
    }

    /**
     * Unsets the file object so it can release the file pointer.
     */
    public function close()
    {
        $this->fileObject = null;
    }

    /**
     * Writes data to the stream while obeying the maximum line length of 255 characters.
     *
     * If $this->requiresWhitespace is true, it means that the last token requires a whitespace character in case the
     * next token begins with an alphanumeric character. If that is the case, the callee should set $prependWhitespace
     * to true, so that a whitespace is appended in that case, which may either be a space or a newline.
     *
     * @param string $data
     * @param bool   $prependWhitespace
     */
    private function writeData($data, $prependWhitespace)
    {
        if (null === $this->fileObject) {
            throw new WriterClosedException('The writer object was closed');
        }

        $dataSize = strlen($data);

        if ($this->requiresWhitespace && $prependWhitespace) {
            $dataSize += 1;
        }

        if ($this->currentLineLength + $dataSize >= 255) {
            $this->fileObject->fwrite("\n");
            $this->currentLineLength = 0;
            $this->requiresWhitespace = false;
            $dataSize -= 1;
        } elseif ($this->requiresWhitespace && $prependWhitespace) {
            $this->fileObject->fwrite(' ');
        }

        $this->fileObject->fwrite($data);
        $this->currentLineLength += $dataSize;
    }
}
