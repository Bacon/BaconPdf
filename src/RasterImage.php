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
use Bacon\Pdf\Writer\ObjectWriter;
use Imagick;
use Symfony\Component\Yaml\Exception\RuntimeException;

final class RasterImage
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @param  ObjectWriter $objectWriter
     * @param  string       $filename
     * @param  string       $pdfVersion
     * @param  bool         $useLossyCompression
     * @param  int          $compressionQuality
     * @throws DomainException
     * @throws RuntimeException
     */
    public function __construct(
        ObjectWriter $objectWriter,
        $filename,
        $pdfVersion,
        $useLossyCompression,
        $compressionQuality
    ) {
        if ($compressionQuality < 0 || $compressionQuality > 100) {
            throw new DomainException('Compression quality must be a value between 0 and 100');
        }

        $image = new Imagick($filename);
        $image->stripImage();

        $this->width = $image->getImageWidth();
        $this->height = $image->getImageHeight();

        $filter     = $this->determineFilter($useLossyCompression, $pdfVersion);
        $colorSpace = $this->determineColorSpace($image);
        $this->setFitlerParameters($image, $filter, $colorSpace, $compressionQuality);

        $shadowMaskInData = null;
        $shadowMaskId = null;

        if (Imagick::ALPHACHANNEL_UNDEFINED !== $image->getImageAlphaChannel()) {
            if (version_compare($pdfVersion, '1.4', '>=')) {
                throw new RuntimeException('Transparent images require PDF version 1.4 or higher');
            }

            if ($filter === 'JPXDecode') {
                $shadowMaskInData = 1;
            } else {
                $shadowMaskId = $this->createShadowMask($objectWriter, $image, $filter);
            }
        }

        $streamData = $image->getImageBlob();
        $image->clear();

        $this->id = $objectWriter->startObject();
        $objectWriter->startDictionary();
        $this->writeCommonDictionaryEntries($objectWriter, $colorSpace, strlen($streamData), $filter);

        if (null !== $shadowMaskInData) {
            $objectWriter->writeName('SMaskInData');
            $objectWriter->writeNumber($shadowMaskInData);
        } elseif (null !== $shadowMaskId) {
            $objectWriter->writeName('SMask');
            $objectWriter->writeIndirectReference($shadowMaskId);
        }

        $objectWriter->startStream();
        $objectWriter->writeRaw($streamData);
        $objectWriter->endStream();
        $objectWriter->endObject();
    }

    /**
     * Returns the object number of the imported image.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the width of the image in pixels.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns the height of the image in pixels.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param  bool   $useLossyCompression
     * @param  string $pdfVersion
     * @return string
     */
    private function determineFilter($useLossyCompression, $pdfVersion)
    {
        if (!$useLossyCompression) {
            return 'FlateDecode';
        }

        if (version_compare($pdfVersion, '1.5', '>=')) {
            return 'JPXDecode';
        }

        return 'DCTDecode';
    }

    /**
     * Determines the color space of an image.
     *
     * @param  Imagick $image
     * @return string
     * @throws DomainException
     */
    private function determineColorSpace(Imagick $image)
    {
        switch ($image->getColorSpace()) {
            case Imagick::COLORSPACE_GRAY:
                return 'DeviceGray';

            case Imagick::COLORSPACE_RGB:
                return 'DeviceRGB';

            case Imagick::COLORSPACE_CMYK:
                return 'DeviceCMYK';
        }

        throw new DomainException('Image has an unsupported colorspace, must be gray, RGB or CMYK');
    }

    /**
     * Creates a shadow mask from an image's alpha channel.
     *
     * @param  ObjectWriter $objectWriter
     * @param  Imagick      $image
     * @param  string       $filter
     * @return int
     */
    private function createShadowMask(ObjectWriter $objectWriter, Imagick $image, $filter)
    {
        $shadowMask = clone $image;
        $shadowMask->separateImageChannel(Imagick::CHANNEL_ALPHA);

        if ('FlateDecode' === $filter) {
            $image->setImageFormat('GRAY');
        }
        
        $streamData = $shadowMask->getImageBlob();
        $shadowMask->clear();

        $id = $objectWriter->startObject();

        $objectWriter->startDictionary();
        $this->writeCommonDictionaryEntries($objectWriter, 'DeviceGray', strlen($streamData), $filter);
        $objectWriter->endDictionary();

        $objectWriter->startStream();
        $objectWriter->writeRaw($streamData);
        $objectWriter->endStream();

        $objectWriter->endObject();
        return $id;
    }

    /**
     * Writes common dictionary entries shared between actual images and their soft masks.
     *
     * @param ObjectWriter $objectWriter
     * @param string       $colorSpace
     * @param int          $length
     * @param string       $filter
     * @param int|null     $shadowMaskId
     */
    private function writeCommonDictionaryEntries(ObjectWriter $objectWriter, $colorSpace, $length, $filter)
    {
        $objectWriter->writeName('Type');
        $objectWriter->writeName('XObject');

        $objectWriter->writeName('Subtype');
        $objectWriter->writeName('Image');

        $objectWriter->writeName('Width');
        $objectWriter->writeNumber($this->width);

        $objectWriter->writeName('Height');
        $objectWriter->writeNumber($this->height);

        $objectWriter->writeName('ColorSpace');
        $objectWriter->writeName($colorSpace);

        $objectWriter->writeName('BitsPerComponent');
        $objectWriter->writeNumber(8);

        $objectWriter->writeName('Length');
        $objectWriter->writeNumber($length);

        $objectWriter->writeName('Filter');
        $objectWriter->writeName($filter);
    }

    /**
     * Sets the filter parameters for the image.
     *
     * @param Imagick $image
     * @param string  $filter
     * @param string  $colorSpace
     * @param int     $compressionQuality
     */
    private function setFitlerParameters(Imagick $image, $filter, $colorSpace, $compressionQuality)
    {
        switch ($filter) {
            case 'JPXDecode':
                $image->setImageFormat('J2K');
                $image->setImageCompression(Imagick::COMPRESSION_JPEG2000);
                break;

            case 'DCTDecode':
                $image->setImageFormat('JPEG');
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                break;

            case 'FlateDecode':
                $image->setImageFormat([
                    'DeviceGray' => 'GRAY',
                    'DeviceRGB' => 'RGB',
                    'DeviceCMYK' => 'CMYK',
                ][$colorSpace]);
                $image->setImageCompression(Imagick::COMPRESSION_ZIP);
                break;
        }

        $image->setImageCompressionQuality($compressionQuality);
    }
}
