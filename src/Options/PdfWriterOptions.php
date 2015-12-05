<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Options;

use Bacon\Pdf\Encryption\AbstractEncryption;
use Bacon\Pdf\Encryption\EncryptionInterface;
use Bacon\Pdf\Encryption\NullEncryption;
use Bacon\Pdf\Exception\DomainException;

final class PdfWriterOptions
{
    /**
     * @var string
     */
    private $pdfVersion;

    /**
     * @var EncryptionOptions|null
     */
    private $encryptionOptions;

    /**
     * @param  string $pdfVersion
     * @throws DomainException
     */
    public function __construct($pdfVersion = '1.7')
    {
        if (!in_array($pdfVersion, ['1.3', '1.4', '1.5', '1.6', '1.7'])) {
            throw new DomainException('PDF version is not in the supported range (1.3 - 1.7)');
        }

        $this->pdfVersion = $pdfVersion;
    }

    /**
     * Returns the PDF version to use for the document.
     *
     * @return string
     */
    public function getPdfVersion()
    {
        return $this->pdfVersion;
    }

    /**
     * Sets encryption options.
     *
     * @param EncryptionOptions $encryptionOptions
     */
    public function setEncryptionOptions(EncryptionOptions $encryptionOptions)
    {
        $this->encryptionOptions = $encryptionOptions;
    }

    /**
     * @param  string $permanentFileIdentifier
     * @return EncryptionInterface
     */
    public function getEncryption($permanentFileIdentifier)
    {
        if (null === $this->encryptionOptions) {
            return new NullEncryption();
        }

        return AbstractEncryption::forPdfVersion($this->pdfVersion, $permanentFileIdentifier, $this->encryptionOptions);
    }
}
