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
use Bacon\Pdf\Encryption\Pdf11Encryption;
use Bacon\Pdf\Encryption\Pdf14Encryption;
use Bacon\Pdf\Encryption\Pdf16Encryption;
use Bacon\Pdf\Encryption\Permissions;
use Bacon\Pdf\Exception\UnexpectedValueException;
use Bacon\Pdf\Exception\UnsupportedPasswordException;
use Bacon\Pdf\Options\EncryptionOptions;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @covers \Bacon\Pdf\Encryption\AbstractEncryption
 */
class AbstractEncryptionTest extends TestCase
{
    public function testForPdfVersion()
    {
        $this->assertInstanceOf(
            Pdf11Encryption::class,
            AbstractEncryption::forPdfVersion('1.3', '', new EncryptionOptions(''))
        );

        $this->assertInstanceOf(
            Pdf14Encryption::class,
            AbstractEncryption::forPdfVersion('1.4', '', new EncryptionOptions(''))
        );

        $this->assertInstanceOf(
            Pdf14Encryption::class,
            AbstractEncryption::forPdfVersion('1.5', '', new EncryptionOptions(''))
        );

        $this->assertInstanceOf(
            Pdf16Encryption::class,
            AbstractEncryption::forPdfVersion('1.6', '', new EncryptionOptions(''))
        );

        $this->assertInstanceOf(
            Pdf16Encryption::class,
            AbstractEncryption::forPdfVersion('1.7', '', new EncryptionOptions(''))
        );
    }

    public function testTooLongishUserPassword()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password is longer than 32 characters');
        $this->getAbstractEncryption()->__construct('', str_repeat('a', 33), '', Permissions::allowNothing());
    }

    public function testTooLongishOwnerPassword()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password is longer than 32 characters');
        $this->getAbstractEncryption()->__construct('', '', str_repeat('a', 33), Permissions::allowNothing());
    }

    public function testUserPasswordWithInvalidCharacters()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password contains non-latin-1 characters');
        $this->getAbstractEncryption()->__construct('', 'Ŧ', '', Permissions::allowNothing());
    }

    public function testOwnerPasswordWithInvalidCharacters()
    {
        $this->setExpectedException(UnsupportedPasswordException::class, 'Password contains non-latin-1 characters');
        $this->getAbstractEncryption()->__construct('', '', 'Ŧ', Permissions::allowNothing());
    }

    public function testAbstractReturnsInvalidKeyLength()
    {
        $this->setExpectedException(UnexpectedValueException::class, 'Key length must be either 40 or 128');
        $this->getAbstractEncryption(100)->__construct('', '', '', Permissions::allowNothing());
    }

    /**
     * @return AbstractEncryption
     */
    private function getAbstractEncryption($keyLength = 128)
    {
        $encryption = $this->getMockForAbstractClass(AbstractEncryption::class, [], '', false);
        $encryption->expects($this->any())->method('getKeyLength')->willReturn($keyLength);
        $encryption->expects($this->any())->method('getRevision')->willReturn(2);
        $encryption->expects($this->any())->method('getAlgorithm')->willReturn(1);
        return $encryption;
    }
}
