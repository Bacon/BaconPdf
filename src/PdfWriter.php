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
use Bacon\Pdf\Writer\DocumentWriter;
use Bacon\Pdf\Writer\ObjectWriter;
use Bacon\Pdf\Writer\PageWriter;
use SplFileObject;

class PdfWriter
{
    /**
     * @var PdfWriterOptions
     */
    private $options;

    /**
     * @var ObjectWriter
     */
    private $objectWriter;

    /**
     * @var DocumentWriter
     */
    private $documentWriter;

    /**
     * @var EncryptionInterface
     */
    private $encryption;

    /**
     * @param SplFileObject    $fileObject
     * @param PdfWriterOptions $options
     */
    public function __construct(SplFileObject $fileObject, PdfWriterOptions $options = null)
    {
        if (null === $options) {
            $options = new PdfWriterOptions();
        }

        $this->options = $options;

        $fileIdentifier = md5(microtime(), true);
        $this->objectWriter = new ObjectWriter($fileObject);
        $this->documentWriter = new DocumentWriter($this->objectWriter, $options, $fileIdentifier);
        $this->encryption = $options->getEncryption($fileIdentifier);
    }

    /**
     * Returns the document information object.
     *
     * @return DocumentInformation
     */
    public function getDocumentInformation()
    {
        return $this->documentWriter->getDocumentInformation();
    }

    /**
     * Adds a page to the document.
     *
     * @param  float $width
     * @param  float $height
     * @return Page
     */
    public function addPage($width, $height)
    {
        $pageWriter = new PageWriter($this->objectWriter);
        $this->documentWriter->addPageWriter($pageWriter);

        return new Page($pageWriter, $width, $height);
    }

    /**
     * Ends the document by writing all pending data.
     *
     * While the PDF writer will remove all references to the passed in file object in itself to avoid further writing
     * and to allow the file pointer to be closed, the callee may still have a reference to it. If that is the case,
     * make sure to unset it if you don't need it.
     *
     * Any further attempts to append data to the PDF writer will result in an exception.
     */
    public function endDocument()
    {
        $this->documentWriter->endDocument($this->encryption);
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
     * Make sure to send the appropriate headers beforehand if you are in a web environment.
     *
     * @param  PdfWriterOptions|null $options
     * @return static
     */
    public static function output(PdfWriterOptions $options = null)
    {
        return new static(new SplFileObject('php://stdout', 'wb'), $options);
    }
}
