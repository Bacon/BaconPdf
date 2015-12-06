<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Encryption;

use Bacon\Pdf\Encryption\Pdf16Encryption;
use Bacon\Pdf\Encryption\Permissions;

/**
 * @covers \Bacon\Pdf\Encryption\AbstractEncryption
 * @covers \Bacon\Pdf\Encryption\Pdf16Encryption
 */
class Pdf16EncryptionTest extends AbstractEncryptionTestCase
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

    /**
     * {@inheritdoc}
     */
    protected function createEncryption(
        $userPassword,
        $ownerPassword = null,
        Permissions $userPermissions = null
    ) {
        return new Pdf16Encryption(
            md5('test', true),
            $userPassword,
            $ownerPassword ?: $userPassword,
            $userPermissions ?: Permissions::allowNothing()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function decrypt($encryptedText, $key)
    {
        return openssl_decrypt(
            substr($encryptedText, 16),
            'aes-128-cbc',
            $key,
            OPENSSL_RAW_DATA,
            substr($encryptedText, 0, 16)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedEntry()
    {
        return file_get_contents(__DIR__ . '/_files/pdf16-encrypt-entry.txt');
    }
}
