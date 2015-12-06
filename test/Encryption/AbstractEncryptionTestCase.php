<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Encryption;

use Bacon\Pdf\Encryption\AbstractEncryption;
use Bacon\Pdf\Encryption\Permissions;
use Bacon\PdfTest\TestHelper\MemoryObjectWriter;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

/**
 * @covers \Bacon\Pdf\Encryption\AbstractEncryption
 */
abstract class AbstractEncryptionTestCase extends TestCase
{
    /**
     * @dataProvider encryptionTestData
     * @param string      $plaintext
     * @param string      $userPassword
     * @param string|null $ownerPassword
     * @param int         $objectNumber
     * @param int         $generationNumber
     */
    public function testEncrypt(
        $plaintext,
        $userPassword,
        $ownerPassword,
        $objectNumber,
        $generationNumber
    ) {
        $encryption = $this->createEncryption($userPassword, $ownerPassword);

        $reflectionClass = new ReflectionClass($encryption);
        $reflectionMethod = $reflectionClass->getMethod('computeIndividualEncryptionKey');
        $reflectionMethod->setAccessible(true);
        $key = $reflectionMethod->invoke($encryption, $objectNumber, $generationNumber);

        $encryptedText = $encryption->encrypt($plaintext, $objectNumber, $generationNumber);
        $decryptedText = $this->decrypt($encryptedText, $key);

        $this->assertSame($plaintext, $decryptedText);
    }

    public function testWriteEncryptEntry()
    {
        $encryption = $this->createEncryption('foo', 'bar');
        $memoryObjectWriter = new MemoryObjectWriter();
        $encryption->writeEncryptEntry($memoryObjectWriter);

        $this->assertStringMatchesFormat($this->getExpectedEntry(), $memoryObjectWriter->getData());
    }

    /**
     * @return array
     */
    abstract public function encryptionTestData();

    /**
     * @param  string           $userPassword
     * @param  string|null      $ownerPassword
     * @param  Permissions|null $userPermissions
     * @return AbstractEncryption
     */
    abstract protected function createEncryption(
        $userPassword,
        $ownerPassword = null,
        Permissions $userPermissions = null
    );

    /**
     * @param  string $encryptedText
     * @param  string $key
     * @return string
     */
    abstract protected function decrypt($encryptedText, $key);

    /**
     * @return string
     */
    abstract protected function getExpectedEntry();
}
