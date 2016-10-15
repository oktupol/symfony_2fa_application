<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 14:35
 */

namespace AppBundle\Entity\Auth\TwoFactorAuthentication;

use AppBundle\Entity\Auth\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class BackupCode
 * @package AppBundle\Entity\Auth\TwoFactorAuthentication
 * @ORM\Entity
 * @ORM\Table(name="auth_two_factor_authentication_backup_code")
 */
class BackupCode
{
    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $code
     * @ORM\Column(type="string", length=12)
     */
    private $code;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Auth\User", inversedBy="backupCodes")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return BackupCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\Auth\User $user
     *
     * @return BackupCode
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\Auth\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
