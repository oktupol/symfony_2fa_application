<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 14:39
 */

namespace AppBundle\Entity\Auth;

use Doctrine\ORM\Mapping as ORM;
use u2flib_server\Registration;

/**
 * Class U2FRegistration
 * @package AppBundle\Entity\Auth
 * @ORM\Entity
 * @ORM\Table(name="auth_u2f_registration")
 */
class U2FRegistration
{
    /**
     * @var int $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $keyHandle
     * @ORM\Column(type="string", unique=true)
     */
    private $keyHandle;

    /**
     * @var string $publicKey
     * @ORM\Column(type="string")
     */
    private $publicKey;

    /**
     * @var string $certificate
     * @ORM\Column(type="text")
     */
    private $certificate;

    /**
     * @var int $counter
     * @ORM\Column(type="integer")
     */
    private $counter;

    /**
     * @var User $user
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Auth\User", inversedBy="u2fRegistrations")
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
     * Set keyHandle
     *
     * @param string $keyHandle
     *
     * @return U2FRegistration
     */
    public function setKeyHandle($keyHandle)
    {
        $this->keyHandle = $keyHandle;

        return $this;
    }

    /**
     * Get keyHandle
     *
     * @return string
     */
    public function getKeyHandle()
    {
        return $this->keyHandle;
    }

    /**
     * Set publicKey
     *
     * @param string $publicKey
     *
     * @return U2FRegistration
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Get publicKey
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Set certificate
     *
     * @param string $certificate
     *
     * @return U2FRegistration
     */
    public function setCertificate($certificate)
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * Get certificate
     *
     * @return string
     */
    public function getCertificate()
    {
        return $this->certificate;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     *
     * @return U2FRegistration
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter
     *
     * @return integer
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\Auth\User $user
     *
     * @return U2FRegistration
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

    /**
     * @return Registration
     */
    public function toU2FRegistration() : Registration
    {
        $registration = new Registration();
        $registration->keyHandle = $this->keyHandle;
        $registration->publicKey = $this->publicKey;
        $registration->certificate = $this->certificate;
        $registration->counter = $this->counter;

        return $registration;
    }

    /**
     * @param Registration $registration
     * @return U2FRegistration
     */
    public function fromU2FRegistration(Registration $registration) : U2FRegistration
    {
        $this->keyHandle = $registration->keyHandle;
        $this->publicKey = $registration->publicKey;
        $this->certificate = $registration->certificate;
        $this->counter = $registration->counter;

        return $this;
    }
}
