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
use Bacon\Pdf\Object\DictionaryObject;
use Bacon\Pdf\Object\LiteralStringObject;
use Bacon\Pdf\Object\NameObject;
use Bacon\Pdf\Structure\TextStringType;
use Bacon\Pdf\Type\DateType;
use DateTimeImmutable;

final class Info
{
    /**
     * @var DictionaryObject
     */
    private $dictionary;

    /**
     * @param DictionaryObject $dictionary
     */
    public function __construct(DictionaryObject $dictionary)
    {
        $this->dictionary = $dictionary;
    }

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

            $this->dictionary->set($key, new NameObject($value));
            return;
        }

        $this->dictionary->set($key, new TextStringType($value));
    }

    /**
     * Removes an entry from the information dictionary.
     *
     * @param string $key
     */
    public function remove($key)
    {
        $this->dictionary->remove($key);
    }

    /**
     * Checks whether an entry exists in the information dictionary.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->dictionary->has($key);
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
     * @throws UnexpectedValueException
     */
    public function get($key)
    {
        if ('CreationDate' === $key || 'ModDate' === $key) {
            throw new DomainException('CreationDate and ModDate must be retrieved through their respective methods');
        }

        $object = $this->dictionary->get($key);

        if ('Trapped' === $key) {
            if (!$object instanceof NameObject) {
                throw new UnexpectedValueException(sprintf(
                    'Expected an object of type %s, but got %s',
                    NameObject::class,
                    get_class($object)
                ));
            }

            return $object->getName();
        }

        if (!$object instanceof LiteralStringObject) {
            throw new UnexpectedValueException(sprintf(
                'Expected an object of type %s, but got %s',
                LiteralStringObject::class,
                get_class($object)
            ));
        }

        return $object->getValue();
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
     * @throws UnexpectedValueException
     */
    private function retrieveDate($key)
    {
        $object = $this->dictionary->get($key);

        if (!$object instanceof DateType) {
            throw new UnexpectedValueException(sprintf(
                'Expected an object of type %s, but got %s',
                DateType::class,
                get_class($object)
            ));
        }

        return $object->getDateTime();
    }
}
