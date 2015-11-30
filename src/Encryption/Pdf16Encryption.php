<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Encryption;

class Pdf16Encryption extends Pdf14Encryption
{
    /**
     * {@inheritdoc}
     */
    public function encrypt($plaintext, $objectNumber, $generationNumber)
    {
        if (function_exists('random_bytes')) {
            // As of PHP 7
            $initializationVector = random_bytes(16);
        } else {
            $initializationVector = '';
            mt_srand();

            for ($i = 0; $i < 16; ++$i) {
                $initializationVector .= chr(mt_rand(0, 255));
            }
        }

        return $initializationVector . openssl_encrypt(
            $plaintext,
            'aes-128-cbc',
            $this->computeIndividualEncryptionKey($objectNumber, $generationNumber) . "\x73\x41\x6c\x54",
            '',
            $initializationVector
        );
    }
}
