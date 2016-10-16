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
use AppBundle\Security\TwoFactor\U2FService;
use Doctrine\ORM\EntityManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use u2flib_server\SignRequest;

class U2FProvider implements TwoFactorProviderInterface
{
    /** @var TwigEngine */
    private $twigEngine;

    /** @var U2FService */
    private $u2fservice;

    /** @var FormFactory */
    private $formFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var Router */
    private $router;

    public function __construct(TwigEngine $engine, U2FService $u2fService, FormFactory $formFactory, EntityManager $entityManager, Router $router)
    {
        $this->twigEngine = $engine;
        $this->u2fservice = $u2fService;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function beginAuthentication(AuthenticationContextInterface $context)
    {
        /** @var User $user */
        $user = $context->getUser();

        if (count($user->getU2fRegistrations()) <= 0) {
            return false;
        }

        $context->getSession()->set('u2f_login_challenge', $this->u2fservice->getAuthenticateData($user));

        return true;
    }

    public function requestAuthenticationCode(AuthenticationContextInterface $context)
    {
        /** @var User $user */
        $user = $context->getUser();
        $request = $context->getRequest();

        /** @var SignRequest[] $challenge */
        $challenge = $context->getSession()->get('u2f_login_challenge');

        $registration = new U2FRegistration();
        $form = $this->getU2FRegistrationForm($registration);

        $formData = $request->get($form->getName());

        if ($formData['skip'] === 'skip') {
            return $this->skip($context);
        }

        if (isset($formData['response_input']) && $context->getSession()->get('_csrf/form') == $formData['_token']) {
            $this->u2fservice->doAuthenticate($user, $registration, $challenge, json_decode($formData['response_input']));

            /** @var U2FRegistration $u2fRegistration */
            foreach ($user->getU2fRegistrations() as $u2fRegistration) {
                if ($u2fRegistration->getKeyHandle() === $registration->getKeyHandle()) {
                    $u2fRegistration->setCounter($registration->getCounter());
                    $this->entityManager->persist($u2fRegistration);
                    $this->entityManager->flush();

                    return $this->authenticate($context);
                }
            }

            $context->getSession()->getBag('flash')->set('notice', 'Authentication unsuccessful');
        }

        return $this->twigEngine->renderResponse('security/u2f_2fa.html.twig', array(
            'request' => $challenge[0],
            'form' => $form->createView(),
            'useTrustedOption' => $context->useTrustedOption()
        ));
    }

    private function getU2FRegistrationForm(U2FRegistration $registration) : FormInterface
    {
        return $this->formFactory->createBuilder(FormType::class, $registration)
            ->setMethod('PATCH')
            ->add('response_input', HiddenType::class, array(
                'mapped' => false
            ))
            ->add('skip', HiddenType::class, array(
                'mapped' => false
            ))
            ->getForm();
    }

    private function authenticate(AuthenticationContextInterface $context)
    {
        /** @var AttributeBag $attributeBag */
        $attributeBag = $context->getSession()->getBag('attributes');

        foreach ($attributeBag as $key => $attribute) {
            if (substr($key, 0, strlen('two_factor_')) === 'two_factor_'
                && substr($key, 0, strlen('two_factor_u2f_')) !== 'two_factor_u2f_'
            ) {
                $context->getSession()->set($key, true);
            }
        }

        $context->setAuthenticated(true);
        return new RedirectResponse($this->router->generate('default_index'));
    }

    private function skip(AuthenticationContextInterface $context)
    {
        /** @var AttributeBag $attributeBag */
        $attributeBag = $context->getSession()->getBag('attributes');

        $canSkip = false;
        foreach ($attributeBag as $key => $attribute) {
            if (substr($key, 0, strlen('two_factor_')) === 'two_factor_'
                && substr($key, 0, strlen('two_factor_u2f_')) !== 'two_factor_u2f_'
            ) {
                if ($attribute !== true) {
                    $canSkip = true;
                    break;
                }
            }
        }

        if ($canSkip) {
            $context->setAuthenticated(true);
        }

        return new RedirectResponse($this->router->generate('default_index'));
    }
}