<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf;

use Bacon\Pdf\Writer\ObjectWriter;

/**
 * Rectangle data structure as defiend in section 3.8.4.
 */
final class Rectangle
{
    /**
     * @var float
     */
    private $x1;

    /**
     * @var float
     */
    private $y1;

    /**
     * @var float
     */
    private $x2;

    /**
     * @var float
     */
    private $y2;

    /**
     * Creates a new rectangle.
     *
     * It doesn't matter in which way you specify the corners, as they will internally be normalized.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     */
    public function __construct($x1, $y1, $x2, $y2)
    {
        $this->x1 = min($x1, $x2);
        $this->y1 = min($y1, $y2);
        $this->x2 = max($x1, $x2);
        $this->y2 = max($y1, $y2);
    }

    /**
     * Writes the rectangle object to a writer.
     *
     * @param ObjectWriter $objectWriter
     * @internal
     */
    public function writeRectangleArray(ObjectWriter $objectWriter)
    {
        $objectWriter->startArray();
        $objectWriter->writeNumber($this->x1);
        $objectWriter->writeNumber($this->y1);
        $objectWriter->writeNumber($this->x2);
        $objectWriter->writeNumber($this->y2);
        $objectWriter->endArray();
    }
}
