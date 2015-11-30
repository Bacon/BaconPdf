<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf;

use Bacon\Pdf\Encryption\EncryptionInterface;
use Bacon\Pdf\Writer\ObjectWriter;
use SplFileObject;

class PdfWriter
{
    /**
     * @var ObjectWriter
     */
    private $objectWriter;

    /**
     * @var PdfWriterOptions
     */
    private $options;

    /**
     * @var string
     */
    private $permanentFileIdentifier;

    /**
     * @var string
     */
    private $changingFileIdentifier;

    /**
     * @var EncryptionInterface|null
     */
    private $encryption;

    /**
     * @param SplFileObject $fileObject
     */
    public function __construct(SplFileObject $fileObject, PdfWriterOptions $options)
    {
        $this->objectWriter = new ObjectWriter($fileObject);

        $options->freeze();
        $this->options = $options;

        $this->objectWriter->writeRawLine(sprintf("%PDF-%s", $this->options->getVersion()));
        $this->objectWriter->writeRawLine("%\xff\xff\xff\xff");

        $this->permanentFileIdentifier = hex2bin(md5(microtime()));
        $this->changingFileIdentifierFileIdentifier = $this->permanentFileIdentifier;
    }

    /**
     * Closes the document by writing the file trailer.
     *
     * While the PDF writer will remove all references to the passed in file object in itself to avoid further writing
     * and to allow the file pointer to be closed, the callee may still have a reference to it. If that is the case,
     * make sure to unset it if you don't need it.
     *
     * Any further attempts to append data to the PDF writer will result in an exception.
     */
    public function closeDocument()
    {
        $this->objectWriter->writeRawLine('trailer');
        $this->objectWriter->startDictionary();

        $this->objectWriter->writeName('Id');
        $this->objectWriter->startArray();
        $this->objectWriter->writeHexadecimalString($this->permanentFileIdentifier);
        $this->objectWriter->writeHexadecimalString($this->changingFileIdentifier);
        $this->objectWriter->endArray();

        if (null !== $this->encryption) {
            $this->objectWriter->writeName('Encrypt');
            $this->encryption->writeEncryptDictionary($this->objectWriter);
        }

        $this->objectWriter->endDictionary();
        $this->objectWriter->writeRawLine("%%%EOF");
    }

    /**
     * Creates a PDF writer which writes everything to a file.
     *
     * @param  string $filename
     * @return static
     */
    public static function toFile($filename)
    {
        return new static(new SplFileObject($filename, 'wb'));
    }

    /**
     * Creates a PDF writer which outputs everything to the STDOUT.
     *
     * @return static
     */
    public static function output()
    {
        return new static(new SplFileObject('php://stdout', 'wb'));
    }
}
