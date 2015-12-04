<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Encryption;

use Bacon\Pdf\Writer\ObjectWriter;

class Pdf16Encryption extends Pdf14Encryption
{
    /**
     * {@inheritdoc}
     */
    protected function writeAdditionalEncryptDictionaryEntries(ObjectWriter $objectWriter)
    {
        parent::writeAdditionalEncryptDictionaryEntries($objectWriter);

        $objectWriter->writeName('CF');
        $objectWriter->startDictionary();

        $objectWriter->writeName('StdCF');
        $objectWriter->startDictionary();

        $objectWriter->writeName('Type');
        $objectWriter->writeName('CryptFilter');

        $objectWriter->writeName('CFM');
        $objectWriter->writeName('AESV2');

        $objectWriter->writeName('Length');
        $objectWriter->writeNumber(128);

        $objectWriter->endDictionary();
        $objectWriter->endDictionary();

        $objectWriter->writeName('StrF');
        $objectWriter->writeName('StdCF');

        $objectWriter->writeName('StmF');
        $objectWriter->writeName('StdCF');
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($plaintext, $objectNumber, $generationNumber)
    {
        $initializationVector = openssl_random_pseudo_bytes(16);

        return $initializationVector . openssl_encrypt(
            $plaintext,
            'aes-128-cbc',
            $this->computeIndividualEncryptionKey($objectNumber, $generationNumber) . "\x73\x41\x6c\x54",
            true,
            $initializationVector
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getRevision()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAlgorithm()
    {
        return 4;
    }
}
