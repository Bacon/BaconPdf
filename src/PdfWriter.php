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
use Bacon\Pdf\Encryption\Pdf11Encryption;
use Bacon\Pdf\Encryption\Pdf14Encryption;
use Bacon\Pdf\Encryption\Pdf16Encryption;
use Bacon\Pdf\Encryption\Permissions;
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
     * @var array
     */
    private $objectOffsets = [];

    /**
     * @param SplFileObject    $fileObject
     * @param PdfWriterOptions $options
     */
    public function __construct(SplFileObject $fileObject, PdfWriterOptions $options)
    {
        $this->objectWriter = new ObjectWriter($fileObject);

        $options->freeze();
        $this->options = $options;

        $this->objectWriter->writeRawLine(sprintf("%PDF-%s", $this->options->getVersion()));
        $this->objectWriter->writeRawLine("%\xff\xff\xff\xff");

        $this->changingFileIdentifier = $this->permanentFileIdentifier = md5(microtime(), true);

        if ($this->options->hasEncryption()) {
            $this->encryption = $this->chooseEncryption(
                $this->options->getUserPassword(),
                $this->options->getOwnerPassword(),
                $this->options->getVersion()
            );
        }
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
        $this->objectWriter->close();
    }

    /**
     * Creates a PDF writer which writes everything to a file.
     *
     * @param  string           $filename
     * @param  PdfWriterOptions $options
     * @return static
     */
    public static function toFile($filename, PdfWriterOptions $options)
    {
        return new static(new SplFileObject($filename, 'wb'), $options);
    }

    /**
     * Creates a PDF writer which outputs everything to the STDOUT.
     *
     * @param  PdfWriterOptions $options
     * @return static
     */
    public static function output(PdfWriterOptions $options)
    {
        return new static(new SplFileObject('php://stdout', 'wb'), $options);
    }

    /**
     * Choose an encryption algorithm based on the PDF version.
     *
     * @param string           $version
     * @param string           $userPassword
     * @param string|null      $ownerPassword
     * @param Permissions|null $userPermissions
     */
    private function chooseEncryption(
        $version,
        $userPassword,
        $ownerPassword = null,
        Permissions $userPermissions = null
    ) {
        if (version_compare($version, '1.6', '>=')) {
            return new Pdf16Encryption($ownerPassword, $userPassword, $version, $userPermissions);
        }

        if (version_compare($version, '1.4', '>=')) {
            return new Pdf14Encryption($ownerPassword, $userPassword, $version, $userPermissions);
        }

        return new Pdf11Encryption($ownerPassword, $userPassword, $version, $userPermissions);
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

        if (null !== $this->encryption) {
            $this->objectWriter->writeName('Encrypt');
            $this->encryption->writeEncryptDictionary($this->objectWriter);
        }

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
