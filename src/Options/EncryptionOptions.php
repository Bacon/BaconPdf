<?php
/**
 * BaconPdf
 *
 * @link      http://github.com/Bacon/BaconPdf For the canonical source repository
 * @copyright 2015 Ben Scholzen (DASPRiD)
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Bacon\Pdf\Options;

use Bacon\Pdf\Encryption\Permissions;

final class EncryptionOptions
{
    /**
     * @var string
     */
    private $userPassword;

    /**
     * @var string
     */
    private $ownerPassword;

    /**
     * @var Permissions
     */
    private $userPermissions;

    /**
     * @param string           $userPassword
     * @param string|null      $ownerPassword
     * @param Permissions|null $userPermissions
     */
    public function __construct($userPassword, $ownerPassword = null, Permissions $userPermissions = null)
    {
        $this->userPassword = $userPassword;
        $this->ownerPassword = (null !== $ownerPassword ? $ownerPassword : $userPassword);
        $this->userPermissions = (null !== $userPermissions ? $userPermissions : Permissions::allowEverything());
    }

    /**
     * @return string
     */
    public function getUserPassword()
    {
        return $this->userPassword;
    }

    /**
     * @return string
     */
    public function getOwnerPassword()
    {
        return $this->ownerPassword;
    }

    /**
     * @return Permissions
     */
    public function getUserPermissions()
    {
        return $this->userPermissions;
    }
}
