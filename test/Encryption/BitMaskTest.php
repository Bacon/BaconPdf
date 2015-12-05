<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Encryption;

use Bacon\Pdf\Encryption\BitMask;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @covers \Bacon\Pdf\Encryption\BitMask
 */
class BitMaskTest extends TestCase
{
    public function testDefault()
    {
        $bitMask = new BitMask();
        $this->assertSame(0, $bitMask->toInt());
    }

    public function testSetBit()
    {
        $bitMask = new BitMask();
        $bitMask->set(0, true);
        $bitMask->set(1, true);
        $this->assertSame(3, $bitMask->toInt());
        $bitMask->set(0, false);
        $this->assertSame(2, $bitMask->toInt());
    }
}
