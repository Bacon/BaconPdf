<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Encryption;

use Bacon\Pdf\Encryption\Pdf11Encryption;
use Bacon\Pdf\Encryption\Permissions;
use Bacon\Pdf\Exception\DomainException;

/**
 * @covers \Bacon\Pdf\Encryption\AbstractEncryption
 * @covers \Bacon\Pdf\Encryption\Pdf11Encryption
 */
class Pdf11EncryptionTest extends AbstractEncryptionTest
{
    /**
     * {@inheritdoc}
     */
    public function encryptionTestData()
    {
        return [
            'same-numbers' => ['test', 'foo', null, 1, 1],
            'changed-generation-number' => ['test', 'foo', null, 1, 2],
            'changed-object-number' => ['test', 'foo', null, 2, 1],
            'both-numbers-changed' => ['test', 'foo', null, 2, 2],
            'changed-user-password' => ['test', 'bar', null, 1, 1],
            'added-owner-password' => ['test', 'bar', 'baz', 1, 1],
        ];
    }

    public function testSettingPermissions()
    {
        $this->setExpectedException(DomainException::class, 'This encryption does not support permissions');
        new Pdf11Encryption(md5('test', true), 'bar', null, $this->prophesize(Permissions::class)->reveal());
    }

    /**
     * {@inheritdoc}
     */
    protected function createEncryption(
        $userPassword,
        $ownerPassword = null,
        Permissions $userPermissions = null
    ) {
        return new Pdf11Encryption(md5('test', true), $userPassword, $ownerPassword, $userPermissions);
    }

    /**
     * {@inheritdoc}
     */
    protected function decrypt($encryptedText, $key)
    {
        return openssl_decrypt($encryptedText, 'rc4', $key, OPENSSL_RAW_DATA);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedDictionary()
    {
        return file_get_contents(__DIR__ . '/_files/pdf11-encrypt-dictionary.txt');
    }
}
