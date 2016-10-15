<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 14:28
 */

namespace AppBundle\Entity\Auth\TwoFactorAuthentication;

use AppBundle\Entity\Auth\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class TrustToken
 * @package AppBundle\Entity\Auth\TwoFactorAuthentication
 * @ORM\Entity
 * @ORM\Table(name="auth_two_factor_authentication_trust_token")
 */
class TrustToken
{
    /**
     * @var int $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $token
     * @ORM\Column(type="string", length=128)
     */
    private $token;

    /**
     * @var \DateTime $expiry
     * @ORM\Column(type="datetime")
     */
    private $expiry;

    /**
     * @var User $user
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Auth\User", inversedBy="trustTokens")
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
     * Set token
     *
     * @param string $token
     *
     * @return TrustToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set expiry
     *
     * @param \DateTime $expiry
     *
     * @return TrustToken
     */
    public function setExpiry($expiry)
    {
        $this->expiry = $expiry;

        return $this;
    }

    /**
     * Get expiry
     *
     * @return \DateTime
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\Auth\User $user
     *
     * @return TrustToken
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
