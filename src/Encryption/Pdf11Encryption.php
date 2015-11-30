<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Encryption;

class Pdf11Encryption extends AbstractEncryption
{
    /**
     * {@inheritdoc}
     */
    public function encrypt($plaintext, $objectNumber, $generationNumber)
    {
        return openssl_encrypt(
            $plaintext,
            'rc4',
            $this->computeIndividualEncryptionKey($objectNumber, $generationNumber)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getRevision()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAlgorithm()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeyLength()
    {
        return 40;
    }
}
