<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Writer;

use Bacon\Pdf\Rectangle;
use DomainException;

class PageWriter
{
    /**
     * @var ObjectWriter
     */
    private $objectWriter;

    /**
     * @var int
     */
    private $pageId;

    /**
     * @var Rectangle[]
     */
    private $boxes = [];

    /**
     * @var int|null
     */
    private $rotation;

    /**
     * @var string
     */
    private $contentStream = '';

    /**
     * @param ObjectWriter $objectWriter
     */
    public function __construct(ObjectWriter $objectWriter)
    {
        $this->objectWriter = $objectWriter;
        $this->pageId = $this->objectWriter->allocateObjectId();
    }

    /**
     * Sets a box for the page.
     *
     * @param string    $name
     * @param Rectangle $box
     */
    public function setBox($name, Rectangle $box)
    {
        $this->boxes[$name] = $box;
    }

    /**
     * Sets the rotation of the page.
     *
     * @param  int $degrees
     * @throws DomainException
     */
    public function setRotation($degrees)
    {
        if (!in_array($degrees, [0, 90, 180, 270])) {
            throw new DomainException('Degrees value must be a multiple of 90');
        }

        $this->rotation = $degrees;
    }

    /**
     * Appends data to the content stream.
     *
     * @param string $data
     */
    public function appendContentStream($data)
    {
        $this->contentStream .= $data;
    }

    /**
     * Writes the page contents and definition to the writer.
     *
     * @param  ObjectWriter $objectWriter
     * @param  int          $pageTreeId
     * @return int
     */
    public function writePage(ObjectWriter $objectWriter, $pageTreeId)
    {
        $objectWriter->startObject($this->pageId);
        $objectWriter->startDictionary();
        $objectWriter->writeName('Type');
        $objectWriter->writeName('Page');

        $objectWriter->writeName('Parent');
        $objectWriter->writeIndirectReference($pageTreeId);

        $objectWriter->writeName('Resources');
        $objectWriter->startDictionary();
        $objectWriter->endDictionary();

        $objectWriter->writeName('Contents');
        $objectWriter->startArray();
        $objectWriter->endArray();

        foreach ($this->boxes as $name => $box) {
            $objectWriter->writeName($name);
            $box->writeRectangleArray($objectWriter);
        }

        if (null !== $this->rotation) {
            $objectWriter->writeName('Rotate');
            $objectWriter->writeNumber($this->rotation);
        }

        $objectWriter->endDictionary();
        $objectWriter->endObject();
        return $this->pageId;
    }
}
