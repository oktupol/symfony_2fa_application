<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 21:08
 */

namespace AppBundle\Security\TwoFactor\Provider;


use AppBundle\Entity\Auth\U2FRegistration;
use AppBundle\Entity\Auth\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\TwigBundle\TwigEngine;
use u2flib_server\SignRequest;
use u2flib_server\U2F;

class U2FProvider implements TwoFactorProviderInterface
{
    /** @var TwigEngine */
    private $twigEngine;

    /** @var Router */
    private $router;

    /** @var U2F */
    private $u2flib;

    /** @var SignRequest */
    private $challenge;

    public function __construct(TwigEngine $engine, Router $router)
    {
        $this->twigEngine = $engine;
        $this->router = $router;
    }

    public function beginAuthentication(AuthenticationContextInterface $context)
    {
        /** @var User $user */
        $user = $context->getUser();

        if (count($user->getU2fRegistrations()) <= 0) {
            return false;
        }

        $request = $context->getRequest();
        $appId = ($request->isSecure() ? 'https' : 'http') . '://' . $this->router->getContext()->getHost();
        $this->u2flib = new U2F($appId);

        $this->challenge = $this->u2flib->getAuthenticateData(array_map(function (U2FRegistration $registration) {
            return $registration->toU2FRegistration();
        }, $user->getU2fRegistrations()->toArray()));

        return true;
    }

    public function requestAuthenticationCode(AuthenticationContextInterface $context)
    {
        return $this->twigEngine->renderResponse('security/u2f_2fa.html.twig', array());
    }
}