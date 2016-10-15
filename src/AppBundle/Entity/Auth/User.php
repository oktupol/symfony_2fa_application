<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 14:08
 */

namespace AppBundle\Entity\Auth;

use AppBundle\Entity\Auth\TwoFactorAuthentication\BackupCode;
use AppBundle\Entity\Auth\TwoFactorAuthentication\TrustToken;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedComputerInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 * @package AppBundle\Entity\Auth
 * @ORM\Entity
 * @ORM\Table(name="auth_user")
 */
class User implements AdvancedUserInterface, TwoFactorInterface, TrustedComputerInterface, BackupCodeInterface, \Serializable
{
    /**
     * @var int $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $username
     * @ORM\Column(type="string", length=40, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=40)
     */
    private $username;

    /**
     * @var string $password
     * @ORM\Column(type="string", length=64)
     * @Assert\Length(min=8)
     */
    private $password;

    /**
     * @var boolean $enabled
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTime $lockedUntil
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lockedUntil;

    /**
     * @var \DateTime $accountExpiry
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $accountExpiry;

    /**
     * @var \DateTime $credentialsExpiry
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $credentialsExpiry;

    /**
     * @var Role[] $roles
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Auth\Role", inversedBy="users")
     * @ORM\JoinTable(name="auth_user_roles")
     */
    private $roles;

    /**
     * @var string $googleAuthenticatorSecret
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $googleAuthenticatorSecret;

    /**
     * @var TrustToken[] $trustTokens
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Auth\TwoFactorAuthentication\TrustToken", mappedBy="user", cascade={"persist", "remove"})
     */
    private $trustTokens;

    /**
     * @var BackupCode[] $backupCodes
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Auth\TwoFactorAuthentication\BackupCode", mappedBy="user", cascade={"persist", "remove"})
     */
    private $backupCodes;

    /**
     * @var U2FRegistration[] $u2fRegistrations
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Auth\U2FRegistration", mappedBy="user", cascade={"persist", "remove"})
     */
    private $u2fRegistrations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->enabled = true;

        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->trustTokens = new \Doctrine\Common\Collections\ArrayCollection();
        $this->backupCodes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->u2fRegistrations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->enabled,
            $this->accountExpiry,
            $this->credentialsExpiry
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            $this->enabled,
            $this->accountExpiry,
            $this->credentialsExpiry
            ) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isAccountNonLocked()
    {
        if ($this->lockedUntil === null) {
            return true;
        }

        $now = new \DateTime();

        return $now > $this->lockedUntil;
    }

    /**
     * @return bool
     */
    public function isAccountNonExpired()
    {
        if ($this->accountExpiry === null) {
            return true;
        }

        $now = new \DateTime();

        return $now <= $this->accountExpiry;
    }

    /**
     * @return bool
     */
    public function isCredentialsNonExpired()
    {
        if ($this->credentialsExpiry === null) {
            return true;
        }

        $now = new \DateTime();
        return $now <= $this->credentialsExpiry;
    }

    public function eraseCredentials()
    {
        $this->password = '';
    }

    /**
     * @return string[]
     */
    public function getRoles()
    {
        return array_map(function (Role $role) {
            return $role->getName();
        }, $this->roles->toArray());
    }

    /**
     * @return null|string
     */
    public function getGoogleAuthenticatorSecret()
    {
        return $this->googleAuthenticatorSecret;
    }

    /**
     * @param int $googleAuthenticatorSecret
     */
    public function setGoogleAuthenticatorSecret($googleAuthenticatorSecret)
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isBackupCode($code)
    {
        foreach ($this->backupCodes as $backupCode) {
            if ($backupCode->getCode() === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $code
     */
    public function invalidateBackupCode($code)
    {
        foreach ($this->backupCodes as $backupCode) {
            if ($backupCode->getCode() === $code) {
                $this->backupCodes->removeElement($backupCode);
                return;
            }
        }
    }

    /**
     * @param string $token
     * @return bool
     */
    public function isTrustedComputer($token)
    {
        foreach ($this->trustTokens as $trustToken) {
            if ($trustToken->getToken() === $token) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $token
     * @param \DateTime $validUntil
     */
    public function addTrustedComputer($token, \DateTime $validUntil)
    {
        foreach ($this->trustTokens as $trustToken) {
            if ($trustToken->getToken() === $token) {
                $trustToken->setExpiry($validUntil);
                return;
            }
        }

        $this->trustTokens[] = (new TrustToken())
            ->setToken($token)
            ->setExpiry($validUntil);
    }

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
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Set lockedUntil
     *
     * @param \DateTime $lockedUntil
     *
     * @return User
     */
    public function setLockedUntil($lockedUntil)
    {
        $this->lockedUntil = $lockedUntil;

        return $this;
    }

    /**
     * Get lockedUntil
     *
     * @return \DateTime
     */
    public function getLockedUntil()
    {
        return $this->lockedUntil;
    }

    /**
     * Set accountExpiry
     *
     * @param \DateTime $accountExpiry
     *
     * @return User
     */
    public function setAccountExpiry($accountExpiry)
    {
        $this->accountExpiry = $accountExpiry;

        return $this;
    }

    /**
     * Get accountExpiry
     *
     * @return \DateTime
     */
    public function getAccountExpiry()
    {
        return $this->accountExpiry;
    }

    /**
     * Set credentialsExpiry
     *
     * @param \DateTime $credentialsExpiry
     *
     * @return User
     */
    public function setCredentialsExpiry($credentialsExpiry)
    {
        $this->credentialsExpiry = $credentialsExpiry;

        return $this;
    }

    /**
     * Get credentialsExpiry
     *
     * @return \DateTime
     */
    public function getCredentialsExpiry()
    {
        return $this->credentialsExpiry;
    }

    /**
     * Add role
     *
     * @param \AppBundle\Entity\Auth\Role $role
     *
     * @return User
     */
    public function addRole(Role $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \AppBundle\Entity\Auth\Role $role
     */
    public function removeRole(Role $role)
    {
        $this->roles->removeElement($role);
    }

    /**
     * Add trustToken
     *
     * @param \AppBundle\Entity\Auth\TwoFactorAuthentication\TrustToken $trustToken
     *
     * @return User
     */
    public function addTrustToken(TrustToken $trustToken)
    {
        $this->trustTokens[] = $trustToken;

        return $this;
    }

    /**
     * Remove trustToken
     *
     * @param \AppBundle\Entity\Auth\TwoFactorAuthentication\TrustToken $trustToken
     */
    public function removeTrustToken(TrustToken $trustToken)
    {
        $this->trustTokens->removeElement($trustToken);
    }

    /**
     * Get trustTokens
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTrustTokens()
    {
        return $this->trustTokens;
    }

    /**
     * Add backupCode
     *
     * @param \AppBundle\Entity\Auth\TwoFactorAuthentication\BackupCode $backupCode
     *
     * @return User
     */
    public function addBackupCode(BackupCode $backupCode)
    {
        $this->backupCodes[] = $backupCode;

        return $this;
    }

    /**
     * Remove backupCode
     *
     * @param \AppBundle\Entity\Auth\TwoFactorAuthentication\BackupCode $backupCode
     */
    public function removeBackupCode(BackupCode $backupCode)
    {
        $this->backupCodes->removeElement($backupCode);
    }

    /**
     * Get backupCodes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBackupCodes()
    {
        return $this->backupCodes;
    }

    /**
     * Add u2fRegistration
     *
     * @param \AppBundle\Entity\Auth\U2FRegistration $u2fRegistration
     *
     * @return User
     */
    public function addU2fRegistration(U2FRegistration $u2fRegistration)
    {
        $this->u2fRegistrations[] = $u2fRegistration;

        return $this;
    }

    /**
     * Remove u2fRegistration
     *
     * @param \AppBundle\Entity\Auth\U2FRegistration $u2fRegistration
     */
    public function removeU2fRegistration(U2FRegistration $u2fRegistration)
    {
        $this->u2fRegistrations->removeElement($u2fRegistration);
    }

    /**
     * Get u2fRegistrations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getU2fRegistrations()
    {
        return $this->u2fRegistrations;
    }
}
