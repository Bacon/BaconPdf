<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Encryption;

use Bacon\Pdf\Exception\RuntimeException;
use Bacon\Pdf\Exception\UnexpectedValueException;
use Bacon\Pdf\Exception\UnsupportedPasswordException;
use Bacon\Pdf\Options\EncryptionOptions;
use Bacon\Pdf\Writer\ObjectWriter;

abstract class AbstractEncryption implements EncryptionInterface
{
    // @codingStandardsIgnoreStart
    const ENCRYPTION_PADDING = "\x28\xbf\x4e\x5e\x4e\x75\x8a\x41\x64\x00\x4e\x56\xff\xfa\x01\x08\x2e\x2e\x00\xb6\xd0\x68\x3e\x80\x2f\x0c\xa9\xfe\x64\x53\x69\x7a";
    // @codingStandardsIgnoreEnd

    /**
     * @var string
     */
    private $encryptionKey;

    /**
     * @var string
     */
    private $userEntry;

    /**
     * @var string
     */
    private $ownerEntry;

    /**
     * @var Permissions
     */
    private $userPermissions;

    /**
     * @param  string      $permanentFileIdentifier
     * @param  string      $userPassword
     * @param  string      $ownerPassword
     * @param  Permissions $userPermissions
     * @throws UnexpectedValueException
     */
    public function __construct(
        $permanentFileIdentifier,
        $userPassword,
        $ownerPassword,
        Permissions $userPermissions
    ) {
        // @codeCoverageIgnoreStart
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('The OpenSSL extension is required for encryption');
        }
        // @codeCoverageIgnoreEnd

        $encodedUserPassword  = $this->encodePassword($userPassword);
        $encodedOwnerPassword = $this->encodePassword($ownerPassword);

        $revision  = $this->getRevision();
        $keyLength = $this->getKeyLength() / 8;

        if (!in_array($keyLength, [40 / 8, 128 / 8])) {
            throw new UnexpectedValueException('Key length must be either 40 or 128');
        }

        $this->ownerEntry = $this->computeOwnerEntry(
            $encodedOwnerPassword,
            $encodedUserPassword,
            $revision,
            $keyLength
        );

        if (2 === $revision) {
            list($this->userEntry, $this->encryptionKey) = $this->computeUserEntryRev2(
                $encodedUserPassword,
                $this->ownerEntry,
                $revision,
                $permanentFileIdentifier
            );
        } else {
            list($this->userEntry, $this->encryptionKey) = $this->computeUserEntryRev3OrGreater(
                $encodedUserPassword,
                $revision,
                $keyLength,
                $this->ownerEntry,
                $userPermissions->toInt($revision),
                $permanentFileIdentifier
            );
        }

        $this->userPermissions = $userPermissions;
    }

    /**
     * Returns an encryption fitting for a specific PDF version.
     *
     * @param  string            $pdfVersion
     * @param  string            $permanentFileIdentifier
     * @param  EncryptionOptions $options
     * @return EncryptionInterface
     */
    public static function forPdfVersion($pdfVersion, $permanentFileIdentifier, EncryptionOptions $options)
    {
        if (version_compare($pdfVersion, '1.6', '>=')) {
            return new Pdf16Encryption(
                $permanentFileIdentifier,
                $options->getUserPassword(),
                $options->getOwnerPassword(),
                $options->getUserPermissions()
            );
        }

        if (version_compare($pdfVersion, '1.4', '>=')) {
            return new Pdf14Encryption(
                $permanentFileIdentifier,
                $options->getUserPassword(),
                $options->getOwnerPassword(),
                $options->getUserPermissions()
            );
        }

        return new Pdf11Encryption(
            $permanentFileIdentifier,
            $options->getUserPassword(),
            $options->getOwnerPassword(),
            $options->getUserPermissions()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function writeEncryptDictionary(ObjectWriter $objectWriter)
    {
        $objectWriter->writeName('Encrypt');
        $objectWriter->startDictionary();

        $objectWriter->writeName('Filter');
        $objectWriter->writeName('Standard');

        $objectWriter->writeName('V');
        $objectWriter->writeNumber($this->getAlgorithm());

        $objectWriter->writeName('R');
        $objectWriter->writeNumber($this->getRevision());

        $objectWriter->writeName('O');
        $objectWriter->writeHexadecimalString($this->ownerEntry);

        $objectWriter->writeName('U');
        $objectWriter->writeHexadecimalString($this->userEntry);

        $objectWriter->writeName('P');
        $objectWriter->writeNumber($this->userPermissions->toInt($this->getRevision()));

        $this->writeAdditionalEncryptDictionaryEntries($objectWriter);

        $objectWriter->endDictionary();
    }

    /**
     * Adds additional entries to the encrypt dictionary if required.
     *
     * @param ObjectWriter $objectWriter
     */
    protected function writeAdditionalEncryptDictionaryEntries(ObjectWriter $objectWriter)
    {
    }

    /**
     * Returns the revision number of the encryption.
     *
     * @return int
     */
    abstract protected function getRevision();

    /**
     * Returns the algorithm number of the encryption.
     *
     * @return int
     */
    abstract protected function getAlgorithm();

    /**
     * Returns the key length to be used.
     *
     * The returned value must be either 40 or 128.
     *
     * @return int
     */
    abstract protected function getKeyLength();

    /**
     * Computes an individual ecryption key for an object.
     *
     * @param  string $objectNumber
     * @param  string $generationNumber
     * @return string
     */
    protected function computeIndividualEncryptionKey($objectNumber, $generationNumber)
    {
        return substr(md5(
            $this->encryptionKey
            . substr(pack('V', $objectNumber), 0, 3)
            . substr(pack('V', $generationNumber), 0, 2),
            true
        ), 0, min(16, strlen($this->encryptionKey) + 5));
    }

    /**
     * Encodes a given password into latin-1 and performs length check.
     *
     * @param  string $password
     * @return string
     * @throws UnsupportedPasswordException
     */
    private function encodePassword($password)
    {
        set_error_handler(function () {
        }, E_NOTICE);
        $encodedPassword = iconv('UTF-8', 'ISO-8859-1', $password);
        restore_error_handler();

        if (false === $encodedPassword) {
            throw new UnsupportedPasswordException('Password contains non-latin-1 characters');
        }

        if (strlen($encodedPassword) > 32) {
            throw new UnsupportedPasswordException('Password is longer than 32 characters');
        }

        return $encodedPassword;
    }

    /**
     * Computes the encryption key as defined by algorithm 3.2 in 3.5.2.
     *
     * @param  string $password
     * @param  int    $revision
     * @param  int    $keyLength
     * @param  string $ownerEntry
     * @param  int    $permissions
     * @param  string $permanentFileIdentifier
     * @param  bool   $encryptMetadata
     * @return string
     */
    private function computeEncryptionKey(
        $password,
        $revision,
        $keyLength,
        $ownerEntry,
        $permissions,
        $permanentFileIdentifier,
        $encryptMetadata = true
    ) {
        $string = substr($password . self::ENCRYPTION_PADDING, 0, 32)
                . $ownerEntry
                . pack('V', $permissions)
                . $permanentFileIdentifier;

        if ($revision >= 4 && $encryptMetadata) {
            $string .= "\0xff\0xff\0xff\0xff";
        }

        $hash = md5($string, true);

        if ($revision >= 3) {
            for ($i = 0; $i < 50; ++$i) {
                $hash = md5(substr($hash, 0, $keyLength), true);
            }

            return substr($hash, 0, $keyLength);
        }

        return substr($hash, 0, 5);
    }

    /**
     * Computes the owner entry as defined by algorithm 3.3 in 3.5.2.
     *
     * @param  string $ownerPassword
     * @param  string $userPassword
     * @param  int    $revision
     * @param  int    $keyLength
     * @return string
     */
    private function computeOwnerEntry($ownerPassword, $userPassword, $revision, $keyLength)
    {
        $hash = md5(substr($ownerPassword . self::ENCRYPTION_PADDING, 0, 32), true);

        if ($revision >= 3) {
            for ($i = 0; $i < 50; ++$i) {
                $hash = md5($hash, true);
            }

            $key = substr($hash, 0, $keyLength);
        } else {
            $key = substr($hash, 0, 5);
        }

        $value = openssl_encrypt(substr($userPassword . self::ENCRYPTION_PADDING, 0, 32), 'rc4', $key, true);

        if ($revision >= 3) {
            $value = self::applyRc4Loop($value, $key, $keyLength);
        }

        return $value;
    }

    /**
     * Computes the user entry (rev 2) as defined by algorithm 3.4 in 3.5.2.
     *
     * @param  string $userPassword
     * @param  string $ownerEntry
     * @param  int    $userPermissionFlags
     * @param  string $permanentFileIdentifier
     * @return string[]
     */
    private function computeUserEntryRev2($userPassword, $ownerEntry, $userPermissionFlags, $permanentFileIdentifier)
    {
        $key = self::computeEncryptionKey(
            $userPassword,
            2,
            5,
            $ownerEntry,
            $userPermissionFlags,
            $permanentFileIdentifier
        );

        return [
            openssl_encrypt(self::ENCRYPTION_PADDING, 'rc4', $key, true),
            $key
        ];
    }

    /**
     * Computes the user entry (rev 3 or greater) as defined by algorithm 3.5 in 3.5.2.
     *
     * @param  string $userPassword
     * @param  int    $revision
     * @param  int    $keyLength
     * @param  string $ownerEntry
     * @param  int    $permissions
     * @param  string $permanentFileIdentifier
     * @return string[]
     */
    private function computeUserEntryRev3OrGreater(
        $userPassword,
        $revision,
        $keyLength,
        $ownerEntry,
        $permissions,
        $permanentFileIdentifier
    ) {
        $key = self::computeEncryptionKey(
            $userPassword,
            $revision,
            $keyLength,
            $ownerEntry,
            $permissions,
            $permanentFileIdentifier
        );

        $hash  = md5(self::ENCRYPTION_PADDING . $permanentFileIdentifier, true);
        $value = self::applyRc4Loop(openssl_encrypt($hash, 'rc4', $key, true), $key, $keyLength);
        $value .= openssl_random_pseudo_bytes(16);

        return [
            $value,
            $key
        ];
    }

    /**
     * Applies loop RC4 encryption.
     *
     * @param  string $value
     * @param  string $key
     * @param  int    $keyLength
     * @return string
     */
    private function applyRc4Loop($value, $key, $keyLength)
    {
        for ($i = 1; $i <= 19; ++$i) {
            $newKey = '';

            for ($j = 0; $j < $keyLength; ++$j) {
                $newKey .= chr(ord($key[$j]) ^ $i);
            }

            $value = openssl_encrypt($value, 'rc4', $newKey, true);
        }

        return $value;
    }
}
