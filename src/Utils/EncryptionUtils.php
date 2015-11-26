<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Utils;

/**
 * Utility methods for encrypting PDF documents.
 */
final class EncryptionUtils
{
    // @codingStandardsIgnoreStart
    const ENCRYPTION_PADDING = "\x28\xbf\x4e\x5e\x4e\x75\x8a\x41\x64\x00\x4e\x56\xff\xfa\x01\x08\x2e\x2e\x00\xb6\xd0\x68\x3e\x80\x2f\x0c\xa9\xfe\x64\x53\x69\x7a";
    // @codingStandardsIgnoreEnd

    private function __construct()
    {
    }

    /**
     * Computes the encryption key as defined by algorithm 3.2 in 3.5.2.
     *
     * @param  string $password
     * @param  int    $revision
     * @param  int    $keyLength
     * @param  string $ownerEntry
     * @param  int    $permissions
     * @param  string $idEntry
     * @param  bool   $encryptMetadata
     * @return string
     */
    public static function computeEncryptionKey(
        $password,
        $revision,
        $keyLength,
        $ownerEntry,
        $permissions,
        $idEntry,
        $encryptMetadata = true
    ) {
        $string = substr($password . self::ENCRYPTION_PADDING, 0, 32)
                . $ownerEntry
                . pack('V', $permissions)
                . $idEntry;

        if ($revision >= 4 && $encryptMetadata) {
            $string .= "\0xff\0xff\0xff\0xff";
        }

        $hash = hex2bin(md5($string));

        if ($revision >= 3) {
            for ($i = 0; $i < 50; ++$i) {
                $hash = hex2bin(md5(substr($hash, 0, $keyLength)));
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
    public static function computeOwnerEntry($ownerPassword, $userPassword, $revision, $keyLength)
    {
        $hash = hex2bin(md5(substr($ownerPassword . self::ENCRYPTION_PADDING, 0, 32)));

        if ($revision >= 3) {
            for ($i = 0; $i < 50; ++$i) {
                $hash = hex2bin(md5($hash));
            }

            $key = substr($hash, 0, $keyLength);
        } else {
            $key = substr($hash, 0, 5);
        }

        $value = self::rc4($key, substr($userPassword . self::ENCRYPTION_PADDING, 0, 32));

        if ($revision >= 3) {
            for ($i = 1; $i <= 19; ++$i) {
                $newKey = '';

                for ($j = 0; $j < $keyLength; ++$j) {
                    $newKey = chr(ord($key[$j]) ^ $i);
                }

                $value = self::rc4($newKey, $value);
            }
        }

        return $value;
    }

    /**
     * Computes the user entry (rev 2) as defined by algorithm 3.4 in 3.5.2.
     *
     * @param  string $userPassword
     * @param  string $ownerEntry
     * @param  int    $userPermissionFlags
     * @param  string $idEntry
     * @return array
     */
    public static function computeUserEntryRev2($userPassword, $ownerEntry, $userPermissionFlags, $idEntry)
    {
        $key = self::computeEncryptionKey($userPassword, 2, 5, $ownerEntry, $userPermissionFlags, $idEntry);

        return [self::rc4($key, self::ENCRYPTION_PADDING), $key];
    }

    /**
     * Computes the user entry (rev 3 or greater) as defined by algorithm 3.5 in 3.5.2.
     *
     * @param  string $userPassword
     * @param  int    $revision
     * @param  int    $keyLength
     * @param  string $ownerEntry
     * @param  int    $permissions
     * @param  string $idEntry
     * @param  bool   $encryptMetadata
     * @return array
     */
    public static function computeUserEntryRev3OrGreater(
        $userPassword,
        $revision,
        $keyLength,
        $ownerEntry,
        $permissions,
        $idEntry
    ) {
        $key   = self::computeEncryptionKey($userPassword, $revision, $keyLength, $ownerEntry, $permissions, $idEntry);
        $hash  = hex2bin(md5(self::ENCRYPTION_PADDING . $idEntry));
        $value = self::rc4($key, $hash);

        for ($i = 1; $i <= 19; ++$i) {
            $newKey = '';

            for ($j = 0; $j < $keyLength; ++$j) {
                $newKey = chr(ord($key[$j]) ^ $i);
            }

            $value = self::rc4($newKey, $value);
        }

        return [$value . str_repeat("\x00", 16), $key];
    }

    /**
     * Native RC4 encryption.
     *
     * @param  string $key
     * @param  string $plaintext
     * @return string
     */
    public static function rc4($key, $plaintext)
    {
        // Key-scheduling algorithm
        $keyLength = strlen($key);

        $s = range(0, 255);
        $j = 0;

        for ($i = 0; $i <= 255; ++$i) {
            $j = ($j + $s[$i] + ord($key[$i % $keyLength]) % 256);
            list($s[$i], $s[$j]) = [$s[$j], $s[$i]];
        }

        // Pseudo-random generation algorithm
        $plaintextLength = strlen($plaintext);
        $result          = '';

        $i = 0;
        $j = 0;

        for ($x = 0; $x < $plaintextLength; ++$x) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            list($s[$i], $s[$j]) = [$s[$j], $s[$i]];

            $k = $s[($s[$i] + $s[$j]) % 256];
            $result .= chr(ord($plaintext[$x]) ^ $k);
        }

        return $result;
    }
}
