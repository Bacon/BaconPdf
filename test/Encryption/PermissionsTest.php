<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\PdfTest\Encryption;

use Bacon\Pdf\Encryption\Permissions;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

/**
 * @covers \Bacon\Pdf\Encryption\Permissions
 */
class PermissionsTest extends TestCase
{
    public function testZeroPermissions()
    {
        $permissions = Permissions::allowNothing();
        $this->assertSame(0, $permissions->toInt(2));
        $this->assertSame(0, $permissions->toInt(3));
    }

    public function testFullPermissions()
    {
        $permissions = Permissions::allowEverything();
        $this->assertSame(60, $permissions->toInt(2));
        $this->assertSame(3900, $permissions->toInt(3));
    }

    /**
     * @dataProvider individualPermissions
     */
    public function testIndividualPermissions($flagPosition, $rev2Value, $rev3Value)
    {
        $args = array_fill(0, 8, false);
        $args[$flagPosition] = true;

        $reflectionClass = new ReflectionClass(Permissions::class);
        $permissions = $reflectionClass->newInstanceArgs($args);
        $this->assertSame($rev2Value, $permissions->toInt(2));
        $this->assertSame($rev3Value, $permissions->toInt(3));
    }

    /**
     * @return array
     */
    public function individualPermissions()
    {
        return [
            'may-print' => [0, 4, 4],
            'may-print-high-resolution' => [1, 0, 2048],
            'may-modify' => [2, 8, 8],
            'may-copy' => [3, 16, 16],
            'may-annotate' => [4, 32, 32],
            'may-fill-in-forms' => [5, 0, 256],
            'may-extract-for-accessibility' => [6, 0, 512],
            'may-assemble' => [7, 0, 1024],
        ];
    }
}
