<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf;

use Bacon\Pdf\Writer\PageWriter;

final class Page
{
    /**
     * @var PageWriter
     */
    private $pageWriter;

    /**
     * @param PageWriter $pageWriter
     * @param float      $width
     * @param float      $height
     */
    public function __construct(PageWriter $pageWriter, $width, $height)
    {
        $this->pageWriter = $pageWriter;
        $this->pageWriter->setBox('MediaBox', new Rectangle(0, 0, $width, $height));
    }

    /**
     * Sets the crop box to which the page should be cropped to for displaying or printing.
     *
     * @param Rectangle $cropBox
     */
    public function setCropBox(Rectangle $cropBox)
    {
        $this->pageWriter->setBox('CropBox', $cropBox);
    }

    /**
     * Sets the bleed box to which the page should be clipped in a production environment.
     *
     * @param Rectangle $bleedBox
     */
    public function setBleedBox(Rectangle $bleedBox)
    {
        $this->pageWriter->setBox('BleedBox', $bleedBox);
    }

    /**
     * Sets the trim box to which the finished page should be trimmed.
     *
     * @param Rectangle $trimBox
     */
    public function setTrimBox(Rectangle $trimBox)
    {
        $this->pageWriter->setBox('TrimBox', $trimBox);
    }

    /**
     * Sets the art box which contains the meaningful content of the page.
     *
     * @param Rectangle $artBox
     */
    public function setArtBox(Rectangle $artBox)
    {
        $this->pageWriter->setBox('ArtBox', $artBox);
    }

    /**
     * Rotates the page for output.
     *
     * The supplied value must be a multiple of 90, so either 0, 90, 180 oder 270.
     *
     * @param int $degrees
     */
    public function rotate($degrees)
    {
        $this->pageWriter->setRotation($degrees);
    }
}
