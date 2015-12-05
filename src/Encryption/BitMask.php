<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Encryption;

/**
 * Bit mask for representing permissions.
 */
final class BitMask
{
    /**
     * @var int
     */
    private $value = 0;

    /**
     * Sets
     *
     * @param int  $bit
     * @param bool $value
     */
    public function set($bit, $value)
    {
        if ($value) {
            $this->value |= (1 << $bit);
            return;
        }

        $this->value &= ~(1 << $bit);
    }

    /**
     * @return int
     */
    public function toInt()
    {
        return $this->value;
    }
}
