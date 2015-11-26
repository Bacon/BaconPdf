<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Object;

/**
 * Array object as defined by section 3.2.5
 */
abstract class AbstractObject implements ObjectInterface
{
    /**
     * {@inheritdoc}
     */
    public function getObject()
    {
        return $this;
    }
}
