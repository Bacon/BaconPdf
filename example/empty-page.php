<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

use Bacon\Pdf\PdfWriter;

require_once __DIR__ . '/../vendor/autoload.php';

$writer = PdfWriter::toFile(__DIR__ . '/empty-page.pdf');
$writer->getDocumentInformation()->set('Title', 'Empty Page Example');
$writer->addPage(595, 842);
$writer->endDocument();
