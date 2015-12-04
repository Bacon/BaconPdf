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
use Bacon\Pdf\Exception\UnexpectedValueException;
use Bacon\Pdf\Exception\UnsupportedPasswordException;
use Bacon\PdfTest\TestHelper\MemoryObjectWriter;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

/**
 * @covers \Bacon\Pdf\Encryption\AbstractEncryption
 */
abstract class AbstractEncryptionTest extends TestCase
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

    public function testWriteEncryptDictionary()
    {
        $encryption = $this->createEncryption('foo', 'bar');
        $memoryObjectWriter = new MemoryObjectWriter();
        $encryption->writeEncryptDictionary($memoryObjectWriter);

        $this->assertStringMatchesFormat($this->getExpectedDictionary(), $memoryObjectWriter->getData());
    }

    public function testTooLongishUserPassword()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password is longer than 32 characters');
        $this->createEncryption(str_repeat('a', 33));
    }

    public function testTooLongishOwnerPassword()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password is longer than 32 characters');
        $this->createEncryption('a', str_repeat('a', 33));
    }

    public function testUserPasswordWithInvalidCharacters()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password contains non-latin-1 characters');
        $this->createEncryption('Ŧ');
    }

    public function testOwnerPasswordWithInvalidCharacters()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password contains non-latin-1 characters');
        $this->createEncryption('a', 'Ŧ');
    }

    public function testAbstractReturnsInvalidKeyLength()
    {
        $this->setExpectedException(UnexpectedValueException::class, 'Key length must be either 40 or 128');
        $this->getMockForAbstractClass(AbstractEncryption::class, [md5('test', true), 'foo']);
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
    abstract protected function getExpectedDictionary();
}
