<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Encryption;

use Bacon\Pdf\Encryption\NullEncryption;
use Bacon\PdfTest\TestHelper\MemoryObjectWriter;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @covers \Bacon\Pdf\Encryption\NullEncryption
 */
class NullEncryptionTest extends TestCase
{
    public function testEncryptReturnsPlaintext()
    {
        $encryption = new NullEncryption();
        $this->assertSame('foo', $encryption->encrypt('foo', 1, 1));
    }

    public function testWriteEncryptEntryWritesNothing()
    {
        $encryption = new NullEncryption();
        $objectWriter = new MemoryObjectWriter();
        $encryption->writeEncryptEntry($objectWriter);
        $this->assertSame('', $objectWriter->getData());
    }
}
