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
use Bacon\Pdf\Options\PdfWriterOptions;
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
     * @var EncryptionInterface
     */
    private $encryption;

    /**
     * @var DocumentInformation
     */
    private $documentInformation;

    /**
     * @var array
     */
    private $objectOffsets = [];

    /**
     * @param SplFileObject    $fileObject
     * @param PdfWriterOptions $options
     */
    public function __construct(SplFileObject $fileObject, PdfWriterOptions $options = null)
    {
        if (null === $options) {
            $options = new PdfWriterOptions();
        }

        $this->objectWriter = new ObjectWriter($fileObject);
        $this->objectWriter->writeRawLine(sprintf("%PDF-%s", $this->options->getVersion()));
        $this->objectWriter->writeRawLine("%\xff\xff\xff\xff");

        $this->permanentFileIdentifier = $this->changingFileIdentifier = md5(microtime(), true);
        $this->encryption = $options->getEncryption($this->permanentFileIdentifier);
        $this->documentInformation = new DocumentInformation();
    }

    /**
     * @return DocumentInformation
     */
    public function getDocumentInformation()
    {
        return $this->documentInformation;
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
        $xrefOffset = $this->writeXrefTable();
        $this->writeTrailer();
        $this->writeFooter($xrefOffset);
    }

    /**
     * Creates a PDF writer which writes everything to a file.
     *
     * @param  string                $filename
     * @param  PdfWriterOptions|null $options
     * @return static
     */
    public static function toFile($filename, PdfWriterOptions $options = null)
    {
        return new static(new SplFileObject($filename, 'wb'), $options);
    }

    /**
     * Creates a PDF writer which outputs everything to the STDOUT.
     *
     * @param  PdfWriterOptions|null $options
     * @return static
     */
    public static function output(PdfWriterOptions $options = null)
    {
        return new static(new SplFileObject('php://stdout', 'wb'), $options);
    }

    /**
     * Writes the xref table.
     *
     * @return int
     */
    private function writeXrefTable()
    {
        $this->objectWriter->ensureBlankLine();
        $xrefOffset = $this->objectWriter->currentOffset();

        $this->objectWriter->writeRawLine('xref');
        $this->objectWriter->writeRawLine(sprintf('0 %d', count($this->objectOffsets) + 1));
        $this->objectWriter->writeRawLine(sprintf('%010d %05d f ', 0, 65535));

        foreach ($this->objectOffsets as $offset) {
            $this->objectWriter->writeRawLine(sprintf('%010d %05d n ', $offset, 0));
        }

        return $xrefOffset;
    }

    /**
     * Writes the trailer.
     */
    private function writeTrailer()
    {
        $this->objectWriter->ensureBlankLine();
        $this->objectWriter->writeRawLine('trailer');
        $this->objectWriter->startDictionary();

        $this->objectWriter->writeName('Id');
        $this->objectWriter->startArray();
        $this->objectWriter->writeHexadecimalString($this->permanentFileIdentifier);
        $this->objectWriter->writeHexadecimalString($this->changingFileIdentifier);
        $this->objectWriter->endArray();

        $this->encryption->writeEncryptDictionary($this->objectWriter);

        $this->objectWriter->endDictionary();
    }

    /**
     * Writes the footer.
     *
     * @param int $xrefOffset
     */
    private function writeFooter($xrefOffset)
    {
        $this->objectWriter->ensureBlankLine();
        $this->objectWriter->writeRawLine('startxref');
        $this->objectWriter->writeRawLine((string) $xrefOffset);
        $this->objectWriter->writeRawLine("%%%EOF");
    }
}
