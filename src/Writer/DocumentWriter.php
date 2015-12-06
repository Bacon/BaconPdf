<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Writer;

use Bacon\Pdf\DocumentInformation;
use Bacon\Pdf\Encryption\EncryptionInterface;
use Bacon\Pdf\Options\PdfWriterOptions;

class DocumentWriter
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
     * @var int
     */
    private $pageTreeId;

    /**
     * @var PageWriter[]
     */
    private $pageWriters = [];

    /**
     * @var int[]
     */
    private $pageIds = [];

    /**
     * @var DocumentInformation
     */
    private $documentInformation;

    /**
     * @param ObjectWriter $objectWriter
     * @param string       $fileIdentifier
     */
    public function __construct(ObjectWriter $objectWriter, PdfWriterOptions $options, $fileIdentifier)
    {
        $this->objectWriter = $objectWriter;
        $this->options = $options;

        $this->objectWriter->writeRawLine(sprintf("%%PDF-%s", $this->options->getPdfVersion()));
        $this->objectWriter->writeRawLine("%\xff\xff\xff\xff");

        $this->permanentFileIdentifier = $this->changingFileIdentifier = $fileIdentifier;
        $this->pageTreeId = $this->objectWriter->allocateObjectId();
        $this->documentInformation = new DocumentInformation();
    }

    /**
     * Returns the document information object.
     *
     * @return DocumentInformation
     */
    public function getDocumentInformation()
    {
        return $this->documentInformation;
    }

    /**
     * Adds a page writer for the page tree.
     *
     * @param PageWriter $pageWriter
     */
    public function addPageWriter(PageWriter $pageWriter)
    {
        $this->pageWriters[] = $pageWriter;
    }

    /**
     * Ends the document.
     *
     * @param EncryptionInterface $encryption
     */
    public function endDocument(EncryptionInterface $encryption)
    {
        $this->closeRemainingPages();
        $this->writePageTree();
        $documentInformationId = $this->writeDocumentInformation();
        $documentCatalogId = $this->writeDocumentCatalog();

        $xrefOffset = $this->writeCrossReferenceTable();
        $this->writeTrailer($documentInformationId, $documentCatalogId, $encryption);
        $this->writeFooter($xrefOffset);
    }

    /**
     * Closes pages which haven't been explicitly closed yet.
     */
    private function closeRemainingPages()
    {
        foreach ($this->pageWriters as $key => $pageWriter) {
            $this->pageIds[] = $pageWriter->writePage($this->objectWriter, $this->pageTreeId);
            unset($this->pageWriters[$key]);
        }
    }

    /**
     * Writes the page tree.
     */
    private function writePageTree()
    {
        $this->objectWriter->startObject($this->pageTreeId);
        $this->objectWriter->startDictionary();

        $this->objectWriter->writeName('Type');
        $this->objectWriter->writeName('Pages');

        $this->objectWriter->writeName('Kids');
        $this->objectWriter->startArray();

        sort($this->pageIds, SORT_NUMERIC);
        foreach ($this->pageIds as $pageId) {
            $this->objectWriter->writeIndirectReference($pageId);
        }

        $this->objectWriter->endArray();

        $this->objectWriter->writeName('Count');
        $this->objectWriter->writeNumber(count($this->pageIds));

        $this->objectWriter->endDictionary();
        $this->objectWriter->endObject();
    }

    /**
     * Writes the document information.
     *
     * @return int
     */
    private function writeDocumentInformation()
    {
        $id = $this->objectWriter->startObject();
        $this->documentInformation->writeInfoDictionary($this->objectWriter);
        $this->objectWriter->endObject();
        return $id;
    }

    /**
     * Writes the document catalog.
     *
     * @return int
     */
    private function writeDocumentCatalog()
    {
        $id = $this->objectWriter->startObject();
        $this->objectWriter->startDictionary();

        $this->objectWriter->writeName('Type');
        $this->objectWriter->writeName('Catalog');

        $this->objectWriter->writeName('Pages');
        $this->objectWriter->writeIndirectReference($this->pageTreeId);

        $this->objectWriter->endDictionary();
        $this->objectWriter->endObject();
        return $id;
    }

    /**
     * Writes the cross-reference table.
     *
     * @return int
     */
    private function writeCrossReferenceTable()
    {
        $xrefOffset = $this->objectWriter->getCurrentOffset();
        $objectOffsets = $this->objectWriter->getObjectOffsets();
        ksort($objectOffsets, SORT_NUMERIC);

        $this->objectWriter->writeRawLine('xref');
        $this->objectWriter->writeRawLine(sprintf('0 %d', count($objectOffsets) + 1));
        $this->objectWriter->writeRawLine(sprintf('%010d %05d f ', 0, 65535));

        foreach ($objectOffsets as $offset) {
            $this->objectWriter->writeRawLine(sprintf('%010d %05d n ', $offset, 0));
        }

        return $xrefOffset;
    }

    /**
     * Writes the trailer.
     *
     * @param int                 $documentInformationId
     * @param int                 $documentCatalogId
     * @param EncryptionInterface $encryption
     */
    private function writeTrailer($documentInformationId, $documentCatalogId, EncryptionInterface $encryption)
    {
        $this->objectWriter->writeRawLine('trailer');
        $this->objectWriter->startDictionary();

        $this->objectWriter->writeName('Id');
        $this->objectWriter->startArray();
        $this->objectWriter->writeHexadecimalString($this->permanentFileIdentifier);
        $this->objectWriter->writeHexadecimalString($this->changingFileIdentifier);
        $this->objectWriter->endArray();

        $this->objectWriter->writeName('Info');
        $this->objectWriter->writeIndirectReference($documentInformationId);

        $this->objectWriter->writeName('Root');
        $this->objectWriter->writeIndirectReference($documentCatalogId);

        $encryption->writeEncryptEntry($this->objectWriter);

        $this->objectWriter->endDictionary();
    }

    /**
     * Writes the footer.
     *
     * @param int $xrefOffset
     */
    private function writeFooter($xrefOffset)
    {
        $this->objectWriter->writeRawLine('');
        $this->objectWriter->writeRawLine('startxref');
        $this->objectWriter->writeRawLine((string) $xrefOffset);
        $this->objectWriter->writeRawLine("%%%EOF");
    }
}
