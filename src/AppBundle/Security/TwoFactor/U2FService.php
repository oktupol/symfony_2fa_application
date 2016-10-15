<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 22:28
 */

namespace AppBundle\Security\TwoFactor;


use AppBundle\Entity\Auth\U2FRegistration;
use AppBundle\Entity\Auth\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use u2flib_server\Registration;
use u2flib_server\SignRequest;
use u2flib_server\U2F;

class U2FService
{
    private $appId;

    /**
     * @var U2F
     */
    private $u2f;

    function __construct(Router $router, RequestStack $requestStack)
    {
        $lastRequest = $requestStack->getCurrentRequest();
        $this->appId = ($lastRequest->isSecure() ? 'https' : 'http') . '://' . $router->getContext()->getHost();
        $this->u2f = new U2F($this->appId);
    }

    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param User $user
     * @return array
     */
    public function getRegisterData(User $user)
    {
        return $this->u2f->getRegisterData($this->getRegisteredKeys($user));
    }

    /**
     * @param U2FRegistration $registration
     * @param $request
     * @param $response
     * @param bool $includeCert
     * @return U2FRegistration
     */
    public function doRegister(U2FRegistration $registration, $request, $response, $includeCert = true)
    {
        return $registration->fromU2FRegistration(
            $this->u2f->doRegister($request, $response, $includeCert)
        );
    }

    /**
     * @param User $user
     * @return SignRequest[]
     */
    public function getAuthenticateData(User $user)
    {
        return $this->u2f->getAuthenticateData($this->getRegisteredKeys($user));
    }

    /**
     * @param User $user
     * @param U2FRegistration $registration
     * @param $requests
     * @param $response
     * @return U2FRegistration
     */
    public function doAuthenticate(User $user, U2FRegistration $registration, $requests, $response)
    {
        return $registration->fromU2FRegistration(
            $this->u2f->doAuthenticate($requests, $this->getRegisteredKeys($user), $response)
        );
    }

    /**
     * @param User $user
     * @return Registration
     */
    public function getRegisteredKeys(User $user)
    {
        return array_map(function (U2FRegistration $registration) {
            return $registration->toU2FRegistration();
        }, $user->getU2fRegistrations()->toArray());
    }
}