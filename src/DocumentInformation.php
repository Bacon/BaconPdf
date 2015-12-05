<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf;

use Bacon\Pdf\Exception\DomainException;
use Bacon\Pdf\Exception\UnexpectedValueException;
use Bacon\Pdf\Object\LiteralStringObject;
use Bacon\Pdf\Object\NameObject;
use Bacon\Pdf\Type\DateType;
use DateTimeImmutable;
use OutOfBoundsException;

final class DocumentInformation
{
    /**
     * @var array
     */
    private $data = ['Producer' => 'BaconPdf'];

    /**
     * Sets an entry in the information dictionary.
     *
     * The CreationData and ModDate values are restricted for internal use, so trying to set them will trigger an
     * exception. Setting the "Trapped" value is allowed, but it must be one of the values "True", "False" or "Unknown".
     * You can set any key in here, but the following are the standard keys recognized by the PDF standard. Keep in mind
     * that the keys are case-sensitive:
     *
     * Title, Author, Subject, Keywords, Creator, Producer and Trapped.
     *
     * @param  string $key
     * @param  string $value
     * @throws DomainException
     */
    public function set($key, $value)
    {
        if ('CreationDate' === $key || 'ModDate' === $key) {
            throw new DomainException('CreationDate and ModDate must not be set manually');
        }

        if ('Trapped' === $key) {
            if (!in_array($value, ['True', 'False', 'Unknown'])) {
                throw new DomainException('Value for "Trapped" must be either "True", "False" or "Unknown"');
            }

            $this->data['Trapped'] = $value;
            return;
        }

        $this->data[$key] = $value;
    }

    /**
     * Removes an entry from the information dictionary.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Checks whether an entry exists in the information dictionary.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Retrieves the value for a specific entry in the information dictionary.
     *
     * You may retrieve any entry from the information dictionary through this method, except for "CreationData" and
     * "ModDate". Those two entries have their own respective methods to be retrieved.
     *
     * @param  string $key
     * @return string
     * @throws DomainException
     * @throws OutOfBoundsException
     */
    public function get($key)
    {
        if ('CreationDate' === $key || 'ModDate' === $key) {
            throw new DomainException('CreationDate and ModDate must be retrieved through their respective methods');
        }

        if (!array_key_exists($key, $this->data)) {
            throw new OutOfBoundsException(sprintf('Entry for key "%s" not found', $key));
        }

        return $this->data[$key];
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreationDate()
    {
        return $this->retrieveDate('CreationDate');
    }

    /**
     * @return DateTimeImmutable
     */
    public function getModificationDate()
    {
        return $this->retrieveDate('ModDate');
    }

    /**
     * @param  string $key
     * @return DateTimeImmutable
     * @throws OutOfBoundsException
     */
    private function retrieveDate($key)
    {
        if (!array_key_exists($key, $this->data)) {
            throw new OutOfBoundsException(sprintf('Entry for key "%s" not found', $key));
        }

        return $this->data[$key];
    }
}
